<?php
/*
Plugin Name: Store Locator Plus
Plugin URI: https://www.storelocatorplus.com/
Description: Add a location finder or directory to your site in minutes. Extensive add-on library available!
Author: Store Locator Plus
Author URI: https://www.storelocatorplus.com
License: GPL3
Tested up to: 4.7.2
Version: 4.7.6

Text Domain: store-locator-le
Domain Path: /languages/

Copyright 2012 - 2017  Charleston Software Associates (info@storelocatorplus.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


if ( defined( 'SLPLUS_VERSION'   ) === false ) { define( 'SLPLUS_VERSION'    , '4.7.6'                             ); } // Current plugin version.

$min_wp_version  = '3.8';
$min_php_version = '5.2.4';
$slp_plugin_name = __( 'Store Locator Plus' , 'store-locator-le' );

// Check WP Version
//
global $wp_version;
if ( version_compare( $wp_version, $min_wp_version, '<' ) ) {
    add_action(
        'admin_notices',
        create_function(
            '',
            "echo '<div class=\"error\"><p>".
            sprintf(
                __( '%s requires WordPress %s to function properly. ' , 'store-locator-le' ) ,
                $slp_plugin_name,
                $min_wp_version
            ).
            __( 'This plugin has been deactivated.'                                     , 'store-locator-le' ) .
            __( 'Please upgrade WordPress.'                                             , 'store-locator-le' ) .
            "</p></div>';"
        )
    );
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    deactivate_plugins( plugin_basename( __FILE__ ) );
    return;
}


// Check PHP Version
//
// min: 5.2 pathinfo() with path_parts['filename']  @see  http://php.net/manual/en/function.pathinfo.php
//
if ( version_compare( PHP_VERSION , $min_php_version , '<' ) ) {
    add_action(
        'admin_notices',
        create_function(
            '',
            "echo '<div class=\"error\"><p>".
            sprintf(
                __( '%s requires PHP %s to function properly. ' , 'store-locator-le' ) ,
                $slp_plugin_name,
                $min_php_version
            ).
            __( 'This plugin has been deactivated.'                                     , 'store-locator-le' ) .
            __( 'Please upgrade PHP.'                                                   , 'store-locator-le' ) .
            "</p></div>';"
        )
    );
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    deactivate_plugins( plugin_basename( __FILE__ ) );
    return;
}

// Main Plugin Configuration
//

if ( defined( 'SLPLUS_NAME'      ) === false ) { define( 'SLPLUS_NAME'       , __('Store Locator Plus','store-locator-le')); } // Plugin name via gettext.
if ( defined( 'SLPLUS_PREFIX'    ) === false ) { define( 'SLPLUS_PREFIX'     , 'csl-slplus'                         ); } // The shorthand prefix to various option settings, etc.
if ( defined( 'SLP_ADMIN_PAGEPRE') === false ) { define( 'SLP_ADMIN_PAGEPRE' , 'store-locator-plus_page_'           ); } // Admin Page Slug Prefix

$slp_loader_file = 'store-locator-le/store-locator-le.php';
if ( isset( $mu_plugin )  && ( strpos( $mu_plugin , $slp_loader_file ) !== false )  ) {
	$slp_plugin_file = $mu_plugin;
} elseif ( isset( $plugin ) && ( strpos( $plugin , $slp_loader_file ) !== false ) ) {
    $slp_plugin_file = $plugin;
} elseif ( isset( $network_plugin ) && ( strpos( $network_plugin , $slp_loader_file ) !== false )  ) {
    $slp_plugin_file = $network_plugin;
} else {
    $slp_plugin_file = __FILE__;
}

// Test that the SLP file is in MU directory.
if ( strpos( $slp_plugin_file , WPMU_PLUGIN_DIR ) !== false ) {
	$slp_dir = WPMU_PLUGIN_DIR;
} else {
	$slp_dir = WP_PLUGIN_DIR;
}


if ( defined( 'SLPLUS_FILE'      ) === false ) {
	if ( file_exists( WPMU_PLUGIN_DIR . '/' . basename( dirname( $slp_plugin_file ) ) . '/store-locator-le.php' ) ) {
		define( 'SLPLUS_FILE', $slp_plugin_file );
	} elseif ( file_exists( WP_PLUGIN_DIR . '/' . basename( dirname( $slp_plugin_file ) ) . '/store-locator-le.php' ) ) {
        define( 'SLPLUS_FILE', $slp_plugin_file );
	} else {
        define( 'SLPLUS_FILE' , __FILE__ );
	}
} // Pointer to this file.

if ( defined( 'SLPLUS_PLUGINDIR' ) === false ) { define( 'SLPLUS_PLUGINDIR'  , $slp_dir . '/' . basename( dirname( SLPLUS_FILE ) ) . '/' ); }
if ( defined( 'SLPLUS_ICONDIR'   ) === false ) { define( 'SLPLUS_ICONDIR'    , SLPLUS_PLUGINDIR . 'images/icons/'                             ); } // Path to the icon images
if ( defined( 'SLPLUS_PLUGINURL' ) === false ) { define( 'SLPLUS_PLUGINURL'  , plugins_url( '' , SLPLUS_FILE )                                ); } // Fully qualified URL to this plugin directory.
if ( defined( 'SLPLUS_ICONURL'   ) === false ) { define( 'SLPLUS_ICONURL'    , SLPLUS_PLUGINURL . '/images/icons/'                            ); } // Fully qualified URL to the icon images.
if ( defined( 'SLPLUS_BASENAME'  ) === false ) { define( 'SLPLUS_BASENAME'   , plugin_basename( SLPLUS_FILE )                                 ); } // The relative path from the plugins directory

// SLP Uploads Dir
//
if (defined('SLPLUS_UPLOADDIR') === false) {
    $upload_dir = wp_upload_dir('slp');
    $error = $upload_dir['error'];
    if (empty($error)) {
        define('SLPLUS_UPLOADDIR', $upload_dir['path']);
        define('SLPLUS_UPLOADURL', $upload_dir['url']);
    } else {
        $error = preg_replace(
            '/Unable to create directory /',
            'Unable to create directory ' . ABSPATH ,
            $error
        );
        add_action(
            'admin_notices',
            create_function(
                '',
                "echo '<div class=\"error\"><p>".
                __( 'Store Locator Plus upload directory error.' , 'store-locator-le' ) .
                $error .
                "</p></div>';"
            )
        );
        define('SLPLUS_UPLOADDIR', SLPLUS_PLUGINDIR);
        define('SLPLUS_UPLOADURL', SLPLUS_PLUGINURL);
    }
}

if ( ! defined( 'SLPLUS_COREURL' ) ) { define( 'SLPLUS_COREURL'    , SLPLUS_PLUGINURL ); }

global $slplus , $slplus_plugin;
if ( defined('SLPLUS_PLUGINDIR') && ! is_a( $slplus , 'SLPlus' ) ) {
    require_once('include/SLPlus.php');
}