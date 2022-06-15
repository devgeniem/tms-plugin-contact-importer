<?php
/**
 *  Copyright (c) 2021. Geniem Oy
 */

namespace TMS\Plugin\ContactImporter;

/**
 * Place of Business API Controller
 */
class PlaceOfBusinessApiController extends ApiController {

    /**
     * API slug
     */
    const SLUG = 'place_of_business';

    /**
     * Get endpoint slug
     *
     * @return string
     */
    protected function get_slug() : string {
        return self::SLUG;
    }

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

        $file_contents = file_get_contents( $file );

        return ! empty( $file_contents ) ? json_decode( $file_contents, true ) : false;
    }
}
