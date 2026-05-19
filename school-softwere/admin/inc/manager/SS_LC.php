<?php
/**
 * SS_LC - License checker (placeholder).
 *
 * @package SchoolSoftwere
 */

defined( 'ABSPATH' ) || die();

class SS_LC {

    public static function is_valid() {
        return 'active' === SS_LM::status() || true; // Always valid in this build.
    }
}
