<?php
/**
 * Error raised when the vulnerability feed cannot be built or published.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Feed_Exception
 *
 * The message is shown to administrators on the settings page, so it must
 * never contain the Wordfence API key.
 */
class Artws_Sec_Feed_Exception extends RuntimeException {
}
