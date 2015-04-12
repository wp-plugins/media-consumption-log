<?php

/*
  Copyright (C) 2014-2015 Andreas Giemza <andreas@giemza.net>

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License along
  with this program; if not, write to the Free Software Foundation, Inc.,
  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

class MclData {

    const option_name = 'mcl_data';

    public static function get_data() {
        if ( get_option( self::option_name ) === false ) {
            add_option( self::option_name, self::build_data(), null, 'no' );
        }

        $data = get_option( self::option_name );

        if ( $data->plugin_version != PLUGIN_VERSION ) {
            // Build new data
            $new_data = self::build_data();
            // Save the new data
            update_option( self::option_name, $new_data );
            // Return the new data
            return $new_data;
        }

        return $data;
    }

    public static function update_data() {
        if ( get_option( self::option_name ) !== false ) {
            update_option( self::option_name, self::build_data() );
        } else {
            add_option( self::option_name, self::build_data(), null, 'no' );
        }
    }

    public static function create_page() {
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( isset( $_GET["rebuild-data"] ) && $_GET["rebuild-data"] == 1 ) {
            self::update_data();
        }

        if ( isset( $_GET["remove-postmeta-orphans"] ) && $_GET["remove-postmeta-orphans"] == 1 ) {
            self::remove_postmeta_orphans();
        }

        $postmeta_orphans_count = self::count_postmeta_orphans();
        $posts_without_mcl_number = self::get_posts_without_mcl_number();
        $posts_without_mcl_number_count = count( $posts_without_mcl_number );
        ?>
        <div class="wrap">
            <h2>Media Consumption Log - <?php _e( 'Data', 'media-consumption-log' ); ?></h2>

            <h3><?php _e( 'Rebuild data', 'media-consumption-log' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Rebuild data', 'media-consumption-log' ); ?></th>
                    <td><input class="button-primary" type=button onClick="location.href = 'admin.php?page=mcl-rebuild-data&rebuild-data=1'" value="<?php _e( 'Now!', 'media-consumption-log' ); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Number of queries', 'media-consumption-log' ); ?></th>
                    <td><?php echo get_num_queries(); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Execution time', 'media-consumption-log' ); ?></th>
                    <td><?php timer_stop( 1 ); ?></td>
                </tr>
            </table>

            <h3><?php _e( 'Postmeta orphans', 'media-consumption-log' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Number of postmeta orphans', 'media-consumption-log' ); ?></th>
                    <td><?php echo $postmeta_orphans_count; ?></td>
                </tr>
                <?php if ( $postmeta_orphans_count > 0 ) { ?>
                    <tr>
                        <th scope="row"><?php _e( 'Remove', 'media-consumption-log' ); ?></th>
                        <td><input class="button-primary" type=button onClick="location.href = 'admin.php?page=mcl-rebuild-data&remove-postmeta-orphans=1'" value="<?php _e( 'Now!', 'media-consumption-log' ); ?>" /></td>
                    </tr>
                <?php } ?>
            </table>

            <h3><?php _e( 'Posts without mcl_number', 'media-consumption-log' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Number of posts without mcl_number', 'media-consumption-log' ); ?></th>
                    <td><?php echo $posts_without_mcl_number_count; ?></td>
                </tr>
                <?php if ( $posts_without_mcl_number_count > 0 ) { ?>
                    <tr>
                        <th scope="row"><?php _e( 'Posts', 'media-consumption-log' ); ?></th>
                        <td><?php
                            foreach ( $posts_without_mcl_number as $post_without_mcl_number ) {
                                edit_post_link( $post_without_mcl_number->post_title, "", "", $post_without_mcl_number->ID );

                                if ( $post_without_mcl_number != end( $posts_without_mcl_number ) ) {
                                    echo "<br />";
                                }
                            }
                            ?></td>
                    </tr>   
                <?php } ?>
            </table>

        </div>
        <?php
    }

    public static function build_data() {
        // Set the default timezone
        date_default_timezone_set( get_option( 'timezone_string' ) );

        $data = new stdClass;

        $data->plugin_version = PLUGIN_VERSION;

        // Get the categories
        $monitored_categories_serials = MclSettings::get_monitored_categories_serials();
        $monitored_categories_non_serials = MclSettings::get_monitored_categories_non_serials();

        if ( !empty( $monitored_categories_serials ) && !empty( $monitored_categories_non_serials ) ) {
            $categories = get_categories( "hide_empty=0&include=" . MclSettings::get_monitored_categories_serials() . "," . MclSettings::get_monitored_categories_non_serials() );
        } else {
            $categories = array();
        }

        // Get the first post
        $first_post_array = get_posts( "posts_per_page=1&order=asc" );
        $first_post = array_shift( $first_post_array );
        $first_post_date = new DateTime( $first_post->post_date );
        $data->first_post_date = $first_post_date;

        // Get first date an month for the graphs
        if ( MclSettings::get_statistics_daily_count() != 0 ) {
            $first_date = date( 'Y-m-d', strtotime( "-" . (MclSettings::get_statistics_daily_count() - 1) . " day", strtotime( date( 'Y-m-d' ) ) ) );
        } else {
            $first_date = $data->first_post_date->format( 'Y-m-d' );
        }


        if ( MclSettings::get_statistics_monthly_count() != 0 ) {
            $first_month = date( 'Y-m', strtotime( "-" . (MclSettings::get_statistics_monthly_count() - 1) . " month", strtotime( date( 'Y-m' ) ) ) );
        } else {
            $first_month = $data->first_post_date->format( 'Y-m' );
        }

        // Get the number of days since first post
        $date_current = new DateTime( date( 'Y-m-d' ) );
        $number_of_days = $date_current->diff( $first_post_date )->format( "%a" ) + 1;
        $data->number_of_days = $number_of_days;

        // Total consumption of category
        $consumption_total = 0;
        $consumption_average = 0;

        $tags_count_ongoing = 0;
        $tags_count_complete = 0;

        $cat_serial_ongoing = false;
        $cat_serial_complete = false;
        $cat_non_serial = false;

        $data->categories = array();

        foreach ( $categories as $wp_category ) {
            $category = new stdClass;
            $category->term_id = $wp_category->term_id;
            $category->name = $wp_category->name;
            $category->slug = $wp_category->slug;

            self::get_tags_of_category( $category );

            $tags_count_ongoing += $category->mcl_tags_count_ongoing;
            $tags_count_complete += $category->mcl_tags_count_complete;

            // Graph data
            $category->mcl_daily_data = self::get_mcl_number_count_of_category_sorted_by_day( $category->term_id, $first_date );
            $category->mcl_monthly_data = self::get_mcl_number_count_of_category_sorted_by_month( $category->term_id, $first_month );
            $category->mcl_consumption_total = self::get_total_mcl_mumber_count_of_category( $category->term_id );
            $category->mcl_consumption_average = $category->mcl_consumption_total / $number_of_days;

            $consumption_total += $category->mcl_consumption_total;
            $consumption_average += $category->mcl_consumption_average;

            $data->categories[] = $category;

            if ( MclHelper::is_monitored_serial_category( $category->term_id ) && $category->mcl_tags_count_ongoing > 0 ) {
                $cat_serial_ongoing = true;
            }

            if ( MclHelper::is_monitored_serial_category( $category->term_id ) && $category->mcl_tags_count_complete > 0 ) {
                $cat_serial_ongoing = true;
            }

            if ( MclHelper::is_monitored_non_serial_category( $category->term_id ) && $category->mcl_tags_count_ongoing > 0 ) {
                $cat_non_serial = true;
            }
        }

        $data->consumption_total = $consumption_total;
        $data->consumption_average = $consumption_average;

        $data->tags_count_ongoing = $tags_count_ongoing;
        $data->tags_count_complete = $tags_count_complete;
        $data->tags_count_total = $tags_count_ongoing + $tags_count_complete;

        $data->cat_serial_ongoing = $cat_serial_ongoing;
        $data->cat_serial_complete = $cat_serial_complete;
        $data->cat_non_serial = $cat_non_serial;

        return $data;
    }

    private static function get_tags_of_category( $category ) {
        global $wpdb;

        $tags = $wpdb->get_results( "
            Select
                temp.tag_id,
                temp.taxonomy,
                temp.name,
                temp.post_id,
                temp.post_date,
                temp.post_title,
                IFNULL(mcl.complete, 0) AS complete
            FROM
		(
                    SELECT
                        terms2.term_id AS tag_id,
                        t2.taxonomy AS taxonomy,
                        terms2.name AS name,
                        t1.term_id AS cat_id,
                        p2.ID AS post_id,
                        p2.post_date,
                        p2.post_title
                    FROM
                        {$wpdb->prefix}posts AS p1
                        LEFT JOIN {$wpdb->prefix}term_relationships AS r1 ON p1.ID = r1.object_ID
                        LEFT JOIN {$wpdb->prefix}term_taxonomy AS t1 ON r1.term_taxonomy_id = t1.term_taxonomy_id
                        LEFT JOIN {$wpdb->prefix}terms AS terms1 ON t1.term_id = terms1.term_id,
                        {$wpdb->prefix}posts AS p2
                        LEFT JOIN {$wpdb->prefix}term_relationships AS r2 ON p2.ID = r2.object_ID
                        LEFT JOIN {$wpdb->prefix}term_taxonomy AS t2 ON r2.term_taxonomy_id = t2.term_taxonomy_id
                        LEFT JOIN {$wpdb->prefix}terms AS terms2 ON t2.term_id = terms2.term_id
                    WHERE
                        t1.taxonomy = 'category'
                        AND p1.post_status = 'publish'
                        AND terms1.term_id = '{$category->term_id}'
                        AND t2.taxonomy = 'post_tag'
                        AND p2.post_status = 'publish'
                        AND p1.ID = p2.ID
                        AND p2.post_date = (
                            SELECT
                                MAX(dp1.post_date)
                            FROM
                                {$wpdb->prefix}posts AS dp1
                                LEFT JOIN {$wpdb->prefix}term_relationships AS dr1 ON dp1.ID = dr1.object_ID
                                LEFT JOIN {$wpdb->prefix}term_taxonomy AS dt1 ON dr1.term_taxonomy_id = dt1.term_taxonomy_id,
                                {$wpdb->prefix}posts AS dp2
                                LEFT JOIN {$wpdb->prefix}term_relationships AS dr2 ON dp2.ID = dr2.object_ID
                                LEFT JOIN {$wpdb->prefix}term_taxonomy AS dt2 ON dr2.term_taxonomy_id = dt2.term_taxonomy_id
                            WHERE
                                dp1.ID = dp2.ID
                                AND dt1.taxonomy = 'category'
                                AND dp1.post_status = 'publish'
                                AND dt2.taxonomy = 'post_tag'
                                AND dp2.post_status = 'publish'
                                AND dt1.term_id = t1.term_id
                                AND dt2.term_id = t2.term_id)
                    ORDER BY name
                ) AS temp
            LEFT JOIN {$wpdb->prefix}mcl_complete AS mcl ON temp.tag_id = mcl.tag_id AND temp.cat_id = mcl.cat_id
	" );

        $tags_count_ongoing = 0;
        $tags_count_complete = 0;
        $tags_ongoing = array();
        $tags_complete = array();

        foreach ( $tags as $tag ) {
            // Comma in tags
            $tag = MclCommaInTags::comma_tag_filter( $tag );
            // Get tag link
            $tag->tag_link = get_tag_link( $tag->tag_id );

            // Get last post data
            $tag->post_link = get_permalink( $tag->post_id );

            if ( $tag->complete == false ) {
                $tags_count_ongoing++;

                // Tags which start with a number get their own group #
                if ( preg_match( '/^[a-z]/i', trim( $tag->name[0] ) ) ) {
                    $tags_ongoing[strtoupper( $tag->name[0] )][] = $tag;
                } else {
                    $tags_ongoing['#'][] = $tag;
                }
            } else {
                $tags_count_complete++;

                // Tags which start with a number get their own group #
                if ( preg_match( '/^[a-z]/i', trim( $tag->name[0] ) ) ) {
                    $tags_complete[strtoupper( $tag->name[0] )][] = $tag;
                } else {
                    $tags_complete['#'][] = $tag;
                }
            }
        }

        // Sort tag arrays
        $category->mcl_tags_count = $tags_count_ongoing + $tags_count_complete;
        $category->mcl_tags_count_ongoing = $tags_count_ongoing;
        $category->mcl_tags_count_complete = $tags_count_complete;
        $category->mcl_tags_ongoing = $tags_ongoing;
        $category->mcl_tags_complete = $tags_complete;

        return $category;
    }

    private static function get_mcl_number_count_of_category_sorted_by_day( $category_id, $first_date ) {
        global $wpdb;

        $stats = $wpdb->get_results( "
            SELECT DATE_FORMAT(post_date, '%Y-%m-%d') AS date, SUM(meta_value) AS number
            FROM {$wpdb->prefix}posts p
            LEFT OUTER JOIN {$wpdb->prefix}term_relationships r ON r.object_id = p.ID
            LEFT OUTER JOIN {$wpdb->prefix}postmeta m ON m.post_id = p.ID
            WHERE post_status = 'publish'
              AND post_type = 'post'
              AND meta_key = 'mcl_number'
              AND term_taxonomy_id = '{$category_id}'
              AND post_date >= '{$first_date}'
            GROUP BY DATE_FORMAT(post_date, '%Y-%m-%d')
            ORDER BY date DESC
	" );

        return $stats;
    }

    private static function get_mcl_number_count_of_category_sorted_by_month( $category_id, $first_month ) {
        global $wpdb;

        $stats = $wpdb->get_results( "
            SELECT DATE_FORMAT(post_date, '%Y-%m') AS date, SUM(meta_value) AS number
            FROM {$wpdb->prefix}posts p
            LEFT OUTER JOIN {$wpdb->prefix}term_relationships r ON r.object_id = p.ID
            LEFT OUTER JOIN {$wpdb->prefix}postmeta m ON m.post_id = p.ID
            WHERE post_status = 'publish'
              AND post_type = 'post'
              AND meta_key = 'mcl_number'
              AND term_taxonomy_id = '{$category_id}'
              AND post_date >= '{$first_month}'
            GROUP BY DATE_FORMAT(post_date, '%Y-%m')
            ORDER BY date DESC
	" );

        return $stats;
    }

    private static function get_total_mcl_mumber_count_of_category( $category_id ) {
        global $wpdb;

        $stats = $wpdb->get_results( "
            SELECT SUM(meta_value) AS number
            FROM {$wpdb->prefix}posts p
            LEFT OUTER JOIN {$wpdb->prefix}term_relationships r ON r.object_id = p.ID
            LEFT OUTER JOIN {$wpdb->prefix}postmeta m ON m.post_id = p.ID
            WHERE post_status = 'publish'
              AND post_type = 'post'
              AND meta_key = 'mcl_number'
              AND term_taxonomy_id = '{$category_id}'
	" );

        return $stats[0]->number;
    }

    private static function count_postmeta_orphans() {
        global $wpdb;

        $postmeta_orphans = $wpdb->get_results( "
            SELECT *
            FROM {$wpdb->prefix}postmeta pm
            LEFT JOIN {$wpdb->prefix}posts wp ON wp.ID = pm.post_id
            WHERE wp.ID IS NULL
	" );

        return count( $postmeta_orphans );
    }

    private static function remove_postmeta_orphans() {
        global $wpdb;

        $wpdb->get_results( "
            DELETE pm
            FROM {$wpdb->prefix}postmeta pm
            LEFT JOIN {$wpdb->prefix}posts wp ON wp.ID = pm.post_id
            WHERE wp.ID IS NULL
	" );
    }

    private static function get_posts_without_mcl_number() {
        global $wpdb;

        $monitored_categories_serials = MclSettings::get_monitored_categories_serials();
        $monitored_categories_non_serials = MclSettings::get_monitored_categories_non_serials();

        if ( !empty( $monitored_categories_serials ) && !empty( $monitored_categories_non_serials ) ) {
            $monitored_categories = MclSettings::get_monitored_categories_serials() . "," . MclSettings::get_monitored_categories_non_serials();

            $posts_without_mcl_number = $wpdb->get_results( "
            SELECT *
            FROM {$wpdb->prefix}posts as p
            LEFT JOIN {$wpdb->prefix}term_relationships AS r ON p.ID = r.object_ID
            LEFT JOIN {$wpdb->prefix}term_taxonomy AS t ON r.term_taxonomy_id = t.term_taxonomy_id
            WHERE
                p.post_type = 'post'
                AND p.post_status = 'publish'
                AND t.taxonomy = 'category'
                AND t.term_id IN ({$monitored_categories})
                AND NOT EXISTS (
                    SELECT *
                    FROM {$wpdb->prefix}postmeta
                    WHERE meta_key = 'mcl_number'
                    AND post_id = p.ID
                )
	" );

            return $posts_without_mcl_number;
        } else {
            return array();
        }
    }

}