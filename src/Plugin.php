<?php
/**
 * Copyright (c) 2021 Geniem Oy.
 */

namespace TMS\Plugin\ContactImporter;

use WP_CLI;

/**
 * Class Plugin
 *
 * @package TMS\Plugin\ContactImporter
 */
final class Plugin {

    /**
     * Holds the singleton.
     *
     * @var Plugin
     */
    protected static $instance;

    /**
     * Current plugin version.
     *
     * @var string
     */
    protected $version = '';

    /**
     * Get the instance.
     *
     * @return Plugin
     */
    public static function get_instance() : Plugin {
        return self::$instance;
    }

    /**
     * The plugin directory path.
     *
     * @var string
     */
    protected $plugin_path = '';

    /**
     * The plugin root uri without trailing slash.
     *
     * @var string
     */
    protected $plugin_uri = '';

    /**
     * Get the version.
     *
     * @return string
     */
    public function get_version() : string {
        return $this->version;
    }

    /**
     * Get the plugin directory path.
     *
     * @return string
     */
    public function get_plugin_path() : string {
        return $this->plugin_path;
    }

    /**
     * Get the plugin directory uri.
     *
     * @return string
     */
    public function get_plugin_uri() : string {
        return $this->plugin_uri;
    }

    /**
     * Initialize the plugin by creating the singleton.
     *
     * @param string $version     The current plugin version.
     * @param string $plugin_path The plugin path.
     */
    public static function init( $version = '', $plugin_path = '' ) {
        if ( empty( self::$instance ) ) {
            self::$instance = new self( $version, $plugin_path );
            self::$instance->hooks();
            self::$instance->init_cli_commands();
        }
    }

    /**
     * Get the plugin instance.
     *
     * @return Plugin
     */
    public static function plugin() {
        return self::$instance;
    }

    /**
     * Initialize the plugin functionalities.
     *
     * @param string $version     The current plugin version.
     * @param string $plugin_path The plugin path.
     */
    protected function __construct( $version = '', $plugin_path = '' ) {
        $this->version     = $version;
        $this->plugin_path = $plugin_path;
        $this->plugin_uri  = plugin_dir_url( $plugin_path ) . basename( $this->plugin_path );
    }

    /**
     * Add plugin hooks and filters.
     */
    protected function hooks() : void {
    }

    /**
     * Add the WP CLI commands.
     *
     * @return void
     */
    protected function init_cli_commands() : void {
        if ( ( defined( 'WP_CLI' ) && WP_CLI && is_main_site() ) ) {
            WP_CLI::add_command(
                'person import',
                [
                    self::$instance,
                    'cli_person_import',
                ]
            );

            WP_CLI::add_command(
                'place_of_business import',
                [
                    self::$instance,
                    'cli_place_of_business_import',
                ]
            );
        }
    }

    /**
     * Person import callback
     *
     * @return void
     */
    public function cli_person_import() : void {
        WP_CLI::log( 'Start person import' );

        $api       = new PersonApiController();
        $languages = [
            'fi',
            'en',
        ];

        foreach ( $languages as $language ) {
            $api->set_language( $language );

            $file_name = $api->get_file();
            $contacts  = $api->validate_result_set( $api->query() );

            $api->save_to_file( $contacts, $file_name );
        }
    }

    /**
     * Place of Business import callback
     *
     * @return void
     */
    public function cli_place_of_business_import() : void {
        WP_CLI::log( 'Start place_of_business import' );

        $api = new PlaceOfBusinessApiController();

        $results = $api->get();

        if ( empty( $results ) ) {
            return;
        }

        $file_name = $api->get_file();

        $api->save_to_file( $results, $file_name );
    }
}
