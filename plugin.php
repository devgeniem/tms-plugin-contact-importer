<?php
/**
 * Plugin Name: TMS Contact Importer
 * Plugin URI: https://github.com/devgeniem/tms-plugin-contact-importer
 * Description: {{plugin-description}}
 * Version: 0.2.0
 * Requires PHP: 8.1
 * Author: Geniem Oy
 * Author URI: https://geniem.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: tms-pci
 * Domain Path: /languages
 */

use TMS\Plugin\ContactImporter\Plugin;

// Check if Composer has been initialized in this directory.
// Otherwise we just use global composer autoloading.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Get the plugin version.
$plugin_data    = get_file_data( __FILE__, [ 'Version' => 'Version' ], 'plugin' );
$plugin_version = $plugin_data['Version'];

$plugin_path = __DIR__;

// Initialize the plugin.
Plugin::init( $plugin_version, $plugin_path );

if ( ! function_exists( 'tms_plugin_contact_importer' ) ) {
    /**
     * Get the plugin instance.
     *
     * @return Plugin
     */
    function tms_plugin_contact_importer() : Plugin {
        return Plugin::plugin();
    }
}
