<?php

namespace TMS\Plugin\ContactImporter;

/**
 * PlaceOfBusinessFacade for API to WP
 */
class PlaceOfBusinessFacade {

    /**
     * Fields
     *
     * @var array
     */
    private array $fields;

    /**
     * Constructor
     *
     * @param array $fields API fields.
     */
    public function __construct( array $fields ) {
        return $this->set_fields( $fields );
    }

    /**
     * Set fields
     *
     * @param array $fields API Contact fields.
     *
     * @return object
     */
    public function set_fields( array $fields ) : object {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Get fields
     *
     * @return object
     */
    public function get_fields() : object {
        return $this->fields;
    }

    /**
     * Format API response item to place of business
     *
     * @return array
     */
    public function to_array() : array {
        $fields = $this->fields;

        $fields['phone_repeater'] = $this->handle_phone_numbers( $fields );

        return [
            'id'                    => $fields['id'] ?? '',
            'title'                 => $fields['title'] ?? '',
            'description'           => $fields['description'] ?? '',
            'phone_repeater'        => $this->handle_phone_numbers( $fields ),
            'email'                 => $fields['field_email'] ?? '',
            'additional_info'       => $fields['field_additional_information'] ?? '',
            'mail_address_street'   => $fields['field_address_postal']['address_line1'] ?? '',
            'mail_address_zip_code' => $fields['field_address_postal']['postal_code'] ?? '',
            'mail_address_city'     => $fields['field_address_postal']['locality'] ?? '',
        ];
    }

    /**
     * Handle contact phone numbers
     *
     * @param object $fields API contact fields.
     *
     * @return array
     */
    private function handle_phone_numbers( $fields ) : array {
        $numbers = [];

        if ( ! empty( $fields['field_additional_phones'] ) ) {
            foreach ( $fields['field_additional_phones'] as $phone ) {
                $numbers[] = [
                    'phone_text'   => $phone['telephone_supplementary'] ?? '',
                    'phone_number' => $phone['telephone_number'],
                ];
            }
        }

        return $numbers;
    }
}
