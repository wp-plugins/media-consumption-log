<?php

/*
  Plugin Name: Media Consumption Log
  Plugin URI: https://github.com/hurik/wordpress-media-consumption-log
  Description: This plugin helps you to keep track of the tv shows, movies, books, comics, games and other things you are consuming.
  Version: 1.0.0
  Author: Andreas Giemza
  Author URI: http://www.andreasgiemza.de
  License: MIT
  Text Domain: media-consumption-log
  Domain Path: /languages
 */

/*
  Copyright (C) 2014 Andreas Giemza <andreas@giemza.net>

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

require_once dirname( __FILE__ ) . '/admin/MclAdminHooks.php';
require_once dirname( __FILE__ ) . '/admin/MclComplete.php';
require_once dirname( __FILE__ ) . '/admin/MclNumber.php';
require_once dirname( __FILE__ ) . '/admin/MclQuickPost.php';
require_once dirname( __FILE__ ) . '/admin/MclData.php';
require_once dirname( __FILE__ ) . '/admin/MclSettings.php';
require_once dirname( __FILE__ ) . '/admin/MclUnits.php';
require_once dirname( __FILE__ ) . '/helpers/MclHelper.php';
require_once dirname( __FILE__ ) . '/MclCommaInTags.php';
require_once dirname( __FILE__ ) . '/MclHooks.php';
require_once dirname( __FILE__ ) . '/MclStatistics.php';
require_once dirname( __FILE__ ) . '/MclStatus.php';

register_activation_hook( __FILE__, array( 'MclAdminHooks', 'register_activation_hook' ) );

?>