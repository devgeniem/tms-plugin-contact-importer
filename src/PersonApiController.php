<?php
/**
 *  Copyright (c) 2021. Geniem Oy
 */

namespace TMS\Plugin\ContactImporter;

use TMS\Theme\Base\Settings;

/**
 * Person API Controller
 */
class PersonApiController extends ApiController {

    /**
     * API slug
     */
    const SLUG = 'person';

    /**
     * Get results.
     *
     * @return false|mixed
     */
    public function get_results() {
        if ( DPT_PLL_ACTIVE && function_exists( 'pll_current_language' ) ) {
            $lang = pll_current_language();
        }
        else {
            $lang = get_locale();
        }

        $this->set_language( $lang );

        $file = $this->get_file();

        if ( ! file_exists( $file ) ) {
            return false;
        }

        $file_contents = file_get_contents( $file ); // phpcs:ignore

        return ! empty( $file_contents ) ? $this->validate_result_set( json_decode( $file_contents, true ) ) : false;
    }

    /**
     * Get endpoint slug
     *
     * @return string
     */
    protected function get_slug() : string {
        return self::SLUG;
    }

    /**
     * Validate results set from API.
     *
     * @param mixed $contacts Contacts from API.
     *
     * @return array
     */
    public function validate_result_set( $contacts ) : array {
        if ( empty( $contacts ) || ! is_array( $contacts ) ) {
            return [];
        }

        return array_filter( $contacts, function ( $contact ) {
            if ( ! isset( $contact['first_name'], $contact['last_name'] ) ) {
                return false;
            }

            return true;
        } );
    }

    /**
     * Result set callback
     *
     * @param array $results API results.
     *
     * @return array[]
     */
    public function result_set_callback( $results ) {
        return array_map( function ( $contact ) {
            $facade = new PersonFacade( $contact );

            return $facade->prune( $facade->to_contact() );
        }, $results );
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
    protected function do_query( string $slug, array $data = [], array $params = [], array $args = [] ) {
        $response = $this->do_request( $slug, $params, $args );

        if ( ! $this->is_valid_response( $response ) ) {
            return $data;
        }

        $response_data = $this->result_set_callback( $response->data ?? [] );

        if ( ! empty( $response_data ) ) {
            foreach ( $response_data as $item ) {
                $data[] = $item;
            }
        }

        $query_parts = $this->get_link_query_parts( $response->links->next->href ?? '' );

        return empty( $query_parts )
            ? $data
            : $this->do_query( $slug, $data, $query_parts ?? [], $args );
    }
}
