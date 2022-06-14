<?php
/**
 *  Copyright (c) 2021. Geniem Oy
 */

namespace TMS\Plugin\ContactImporter;

use TMS\Theme\Base\Logger;
use WP_CLI;

/**
 * Tampere API Controller
 */
abstract class ApiController {

    /**
     * Output file path.
     */
    const OUTPUT_PATH = '/home/tms-contact-import/';

    /**
     * Request language
     *
     * @var string
     */
    protected string $language = '';

    /**
     * Get file output path.
     *
     * @return string
     */
    protected function get_output_path() : string {
        return WP_ENV === 'development' ? '/tmp/' : self::OUTPUT_PATH;
    }

    /**
     * Get file path and name.
     *
     * @return string
     */
    public function get_file() : string {
        $filename = $this->get_output_path() . 'drupal-' . $this->get_slug();

        if ( ! empty( $this->language ) ) {
            $filename .= "-{$this->language}";
        }

        return "$filename.json";
    }

    /**
     * Set language
     *
     * @param string $lang API language code.
     *
     * @return $this
     */
    public function set_language( string $lang ) : ApiController {
        $this->language = $lang;

        return $this;
    }

    /**
     * Get language
     *
     * @return string
     */
    public function get_language() : string {
        return $this->language;
    }

    /**
     * Get API base url
     *
     * @return string|null
     */
    protected function get_api_base_url() : ?string {
        $url  = env( 'TAMPERE_API_URL' );
        $lang = $this->get_language();

        if ( ! empty( $lang ) && $lang === 'en' ) {
            $url .= '/en';
        }

        $url .= '/api/node';

        return $url;
    }

    /**
     * Get endpoint slug
     *
     * @return string
     */
    abstract protected function get_slug() : string;

    /**
     * Do an API request
     *
     * @param string|array $path         Request path.
     * @param array        $params       Request query parameters.
     * @param array        $request_args Request args.
     *
     * @return bool|mixed
     */
    public function do_request( $path, array $params = [], array $request_args = [] ) {
        if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
            return;
        }

        $base_url = $this->get_api_base_url();

        if ( empty( $base_url ) ) {
            return false;
        }

        if ( is_array( $path ) ) {
            $path = implode( '/', $path );
        }

        $request_url = \add_query_arg(
            $params,
            sprintf(
                '%s/%s?',
                $base_url,
                $path
            )
        );

        WP_CLI::log( 'Fetch: ' . $request_url );

        $response = \wp_remote_get( $request_url, $request_args );

        if ( 200 !== \wp_remote_retrieve_response_code( $response ) ) {
            ( new Logger() )->error( print_r( $response, true ) ); // phpcs:ignore

            return false;
        }

        return \json_decode( wp_remote_retrieve_body( $response ) );
    }

    /**
     * Is the API response valid.
     *
     * @param mixed $response API response body.
     *
     * @return bool
     */
    public function is_valid_response( $response ) : bool {
        return ! ( ! $response || empty( $response ) );
    }

    /**
     * Get all pages from API
     *
     * @return mixed
     */
    public function get() {
        $args = [
            'headers' => [],
            'timeout' => 30,
        ];

        $basic_auth_key = env( 'TAMPERE_API_AUTH' );

        if ( ! empty( $basic_auth_key ) ) {
            $args['headers']['Authorization'] = 'Basic ' . base64_encode( $basic_auth_key ); // phpcs:ignore
        }

        $params = [
            'filter[status]' => 1,
            'page[limit]'    => 50,
        ];

        return $this->do_get( $this->get_slug(), [], $params, $args );
    }

    /**
     * Recursively get all pages from API.
     *
     * @param string $slug   API slug.
     * @param array  $data   Fetched persons.
     * @param array  $params Query params.
     * @param array  $args   Request arguments.
     *
     * @return array
     */
    protected function do_get( string $slug, array $data = [], array $params = [], array $args = [] ) {
        $response = $this->do_request( $slug, $params, $args );

        if ( ! $this->is_valid_response( $response ) ) {
            return $data;
        }

        if ( ! empty( $response->data ) ) {
            foreach ( $response->data as $item ) {
                $data[] = $item;
            }
        }

        $query_parts = $this->get_link_query_parts(
            $response->links->next->href ?? ''
        );

        return empty( $query_parts )
            ? $data
            : $this->do_get( $slug, $data, $query_parts ?? [], $args );
    }

    /**
     * Get query params from link
     *
     * @param string $href Link.
     *
     * @return array
     */
    protected function get_link_query_parts( string $href ) : array {
        $parts = wp_parse_url( $href );

        if ( ! isset( $parts['query'] ) ) {
            return [];
        }

        parse_str( $parts['query'], $query_parts );

        return $query_parts;
    }

    /**
     * Encode data to JSON & write to file.
     *
     * @param array  $data     Data.
     * @param string $filename File name.
     *
     * @return bool True on success.
     */
    public function save_to_file( $data, $file ) : bool {
        $success = ! empty( file_put_contents( $file, json_encode( $data ) ) );

        if ( ! $success ) {
            \WP_CLI::error( 'TMS\Plugin\ContactImporter\ApiController: Failed to write JSON file.' );
        }

        return $success;
    }
}
