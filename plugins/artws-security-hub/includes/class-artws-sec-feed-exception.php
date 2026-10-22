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

	/**
	 * Code of a failure caused by the data itself (too many invalid records,
	 * too few records). Retrying the same data would fail the same way.
	 */
	const REJECTED = 1;
}
