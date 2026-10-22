<?php
/**
 * Reads the members of a large top-level JSON object one at a time.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Json_Object_Reader
 *
 * The Wordfence Intelligence V3 production feed is one JSON object that maps
 * each vulnerability ID to its record, and it is tens of megabytes. Decoding
 * it in one json_decode() call needs several times that in memory, so this
 * reader scans the file in chunks and hands each member's value to a callback
 * as raw JSON, to be decoded on its own.
 *
 * It only tracks strings, escapes and nesting depth to find where a value
 * ends. It does not validate the values: json_decode() does that for each one.
 */
class Artws_Sec_Json_Object_Reader {

	/**
	 * Open stream.
	 *
	 * @var resource
	 */
	private $handle;

	/**
	 * Bytes read per fread() call.
	 *
	 * @var int
	 */
	private $chunk_size;

	/**
	 * Bytes read but not yet released.
	 *
	 * @var string
	 */
	private $buffer = '';

	/**
	 * Read position in $buffer.
	 *
	 * @var int
	 */
	private $pos = 0;

	/**
	 * Start of the token being read. Bytes before it are dropped on the next refill.
	 *
	 * @var int
	 */
	private $mark = 0;

	/**
	 * Whether the stream has no more bytes.
	 *
	 * @var bool
	 */
	private $eof = false;

	/**
	 * Calls $callback( $key, $value_json ) for each member of the object in $path.
	 *
	 * @param string   $path       File path or stream URL (e.g. compress.zlib://...).
	 * @param callable $callback   Receives the member name and its value as raw JSON.
	 * @param int      $chunk_size Bytes read at a time.
	 * @return int Number of members.
	 * @throws Artws_Sec_Feed_Exception When the file cannot be read or is not a JSON object.
	 */
	public static function read( $path, callable $callback, $chunk_size = 1048576 ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streams a large file; WP_Filesystem reads whole files only.
		$handle = fopen( $path, 'rb' );
		if ( false === $handle ) {
			throw new Artws_Sec_Feed_Exception( 'The downloaded feed could not be opened.' );
		}

		try {
			$reader = new self( $handle, $chunk_size );
			return $reader->each( $callback );
		} finally {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Pairs with fopen() above.
		}
	}

	/**
	 * Constructor.
	 *
	 * @param resource $handle     Open stream.
	 * @param int      $chunk_size Bytes read at a time.
	 */
	private function __construct( $handle, $chunk_size ) {
		$this->handle     = $handle;
		$this->chunk_size = max( 1, (int) $chunk_size );
	}

	/**
	 * Walks the members of the top-level object.
	 *
	 * @param callable $callback Receives the member name and its value as raw JSON.
	 * @return int Number of members.
	 * @throws Artws_Sec_Feed_Exception When the input is not a JSON object.
	 */
	private function each( callable $callback ) {
		$this->skip_whitespace();
		if ( "\xEF" === $this->peek() ) {
			$this->skip_bom();
			$this->skip_whitespace();
		}
		if ( '{' !== $this->peek() ) {
			throw new Artws_Sec_Feed_Exception( 'The feed is not a JSON object.' );
		}
		++$this->pos;

		$count = 0;
		$this->skip_whitespace();
		if ( '}' === $this->peek() ) {
			++$this->pos;
		} else {
			while ( true ) {
				$this->skip_whitespace();
				if ( '"' !== $this->peek() ) {
					throw new Artws_Sec_Feed_Exception( 'The feed has a member without a name.' );
				}
				$this->mark = $this->pos;
				$this->scan_string();
				$key = json_decode( substr( $this->buffer, $this->mark, $this->pos - $this->mark ) );
				if ( ! is_string( $key ) ) {
					throw new Artws_Sec_Feed_Exception( 'The feed has a member name that is not valid JSON.' );
				}

				$this->skip_whitespace();
				if ( ':' !== $this->peek() ) {
					throw new Artws_Sec_Feed_Exception( 'The feed has a member without a value.' );
				}
				++$this->pos;
				$this->skip_whitespace();
				if ( null === $this->peek() ) {
					throw new Artws_Sec_Feed_Exception( 'The feed ends in the middle of a member.' );
				}

				$this->mark = $this->pos;
				$this->scan_value();
				$value      = substr( $this->buffer, $this->mark, $this->pos - $this->mark );
				$this->mark = $this->pos;

				$callback( $key, $value );
				++$count;

				$this->skip_whitespace();
				$next = $this->peek();
				if ( ',' === $next ) {
					++$this->pos;
					continue;
				}
				if ( '}' === $next ) {
					++$this->pos;
					break;
				}
				throw new Artws_Sec_Feed_Exception( 'The feed has a member that is not followed by "," or "}".' );
			}
		}

		$this->skip_whitespace();
		if ( null !== $this->peek() ) {
			throw new Artws_Sec_Feed_Exception( 'The feed has data after the closing "}".' );
		}

		return $count;
	}

	/**
	 * Reads more bytes, dropping what lies before $mark.
	 *
	 * @return bool False at the end of the stream.
	 * @throws Artws_Sec_Feed_Exception When the stream cannot be read.
	 */
	private function fill() {
		while ( ! $this->eof ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Streams a large file.
			$chunk = fread( $this->handle, $this->chunk_size );
			if ( false === $chunk ) {
				throw new Artws_Sec_Feed_Exception( 'The downloaded feed could not be read.' );
			}
			if ( '' === $chunk ) {
				if ( feof( $this->handle ) ) {
					$this->eof = true;
				}
				continue;
			}

			$this->buffer = substr( $this->buffer, $this->mark ) . $chunk;
			$this->pos   -= $this->mark;
			$this->mark   = 0;
			return true;
		}
		return false;
	}

	/**
	 * Returns the byte at the read position without consuming it.
	 *
	 * @return string|null Null at the end of the stream.
	 */
	private function peek() {
		if ( $this->pos >= strlen( $this->buffer ) && ! $this->fill() ) {
			return null;
		}
		return $this->buffer[ $this->pos ];
	}

	/**
	 * Skips JSON whitespace between tokens.
	 */
	private function skip_whitespace() {
		$this->mark = $this->pos;
		while ( true ) {
			$this->pos += strspn( $this->buffer, " \t\n\r", $this->pos );
			if ( $this->pos < strlen( $this->buffer ) || ! $this->fill() ) {
				return;
			}
		}
	}

	/**
	 * Skips a UTF-8 byte order mark.
	 *
	 * @throws Artws_Sec_Feed_Exception When the bytes are not a byte order mark.
	 */
	private function skip_bom() {
		$available = strlen( $this->buffer ) - $this->pos;
		while ( $available < 3 && $this->fill() ) {
			$available = strlen( $this->buffer ) - $this->pos;
		}
		if ( "\xEF\xBB\xBF" !== substr( $this->buffer, $this->pos, 3 ) ) {
			throw new Artws_Sec_Feed_Exception( 'The feed is not a JSON object.' );
		}
		$this->pos += 3;
	}

	/**
	 * Moves past the string that starts at the read position.
	 *
	 * @throws Artws_Sec_Feed_Exception When the string is not terminated.
	 */
	private function scan_string() {
		++$this->pos;
		while ( true ) {
			$this->pos += strcspn( $this->buffer, '"\\', $this->pos );
			if ( $this->pos >= strlen( $this->buffer ) ) {
				if ( ! $this->fill() ) {
					throw new Artws_Sec_Feed_Exception( 'The feed ends inside a string.' );
				}
				continue;
			}
			if ( '"' === $this->buffer[ $this->pos ] ) {
				++$this->pos;
				return;
			}
			// A backslash: the next byte is escaped, so it cannot end the string.
			if ( $this->pos + 1 >= strlen( $this->buffer ) && ! $this->fill() ) {
				throw new Artws_Sec_Feed_Exception( 'The feed ends inside a string.' );
			}
			$this->pos += 2;
		}
	}

	/**
	 * Moves past the value that starts at the read position.
	 *
	 * @throws Artws_Sec_Feed_Exception When the value is not terminated.
	 */
	private function scan_value() {
		$first = $this->buffer[ $this->pos ];

		if ( '"' === $first ) {
			$this->scan_string();
			return;
		}

		if ( '{' === $first || '[' === $first ) {
			$depth = 0;
			while ( true ) {
				$this->pos += strcspn( $this->buffer, '"{}[]', $this->pos );
				if ( $this->pos >= strlen( $this->buffer ) ) {
					if ( ! $this->fill() ) {
						throw new Artws_Sec_Feed_Exception( 'The feed ends inside a record.' );
					}
					continue;
				}
				$byte = $this->buffer[ $this->pos ];
				if ( '"' === $byte ) {
					$this->scan_string();
					continue;
				}
				++$this->pos;
				$depth += ( '{' === $byte || '[' === $byte ) ? 1 : -1;
				if ( 0 === $depth ) {
					return;
				}
			}
		}

		// A number, true, false or null: it ends at the next delimiter.
		while ( true ) {
			$this->pos += strcspn( $this->buffer, ",}] \t\n\r", $this->pos );
			if ( $this->pos < strlen( $this->buffer ) || ! $this->fill() ) {
				return;
			}
		}
	}
}
