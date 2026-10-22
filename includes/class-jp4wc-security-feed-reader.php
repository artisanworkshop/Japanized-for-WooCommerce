<?php
/**
 * Reads a vulnerability feed file one slug at a time.
 *
 * @package Japanized_For_WooCommerce
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * JP4WC_Security_Feed_Reader
 *
 * A vulnerability file of the feed (tests/fixtures/security-feed/) is one JSON
 * object whose `vulnerabilities` member maps each slug to its records. The
 * plugin file holds tens of thousands of records, tens of megabytes of JSON,
 * and decoding it in one json_decode() call needs several times that in
 * memory. This reader scans the file in chunks instead: it hands the other
 * members of the object to one callback and each member of `vulnerabilities`
 * to another, as raw JSON to be decoded on its own.
 *
 * It only tracks strings, escapes and nesting depth to find where a value
 * ends. It does not validate the values: json_decode() does that for each one.
 * Adapted from Artws_Sec_Json_Object_Reader in plugins/artws-security-hub/.
 */
class JP4WC_Security_Feed_Reader {

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
	 * Reads the file in $path.
	 *
	 * @param string   $path               File path.
	 * @param callable $on_member          Receives each top-level member other than the `vulnerabilities` object: ( $key, $value_json ).
	 * @param callable $on_vulnerabilities Receives each member of the `vulnerabilities` object: ( $slug, $value_json ).
	 * @param int      $chunk_size         Bytes read at a time.
	 * @return bool Whether the file has a `vulnerabilities` object.
	 * @throws UnexpectedValueException When the file cannot be read or is not a JSON object.
	 */
	public static function read( $path, callable $on_member, callable $on_vulnerabilities, $chunk_size = 1048576 ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streams a large file; WP_Filesystem reads whole files only.
		$handle = fopen( $path, 'rb' );
		if ( false === $handle ) {
			throw new UnexpectedValueException( 'The feed file could not be opened.' );
		}

		try {
			$reader = new self( $handle, $chunk_size );
			return $reader->each( $on_member, $on_vulnerabilities );
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
	 * Walks the top-level object and the `vulnerabilities` object in it.
	 *
	 * @param callable $on_member          Receives each top-level member other than the `vulnerabilities` object.
	 * @param callable $on_vulnerabilities Receives each member of the `vulnerabilities` object.
	 * @return bool Whether the file has a `vulnerabilities` object.
	 * @throws UnexpectedValueException When the input is not a JSON object.
	 */
	private function each( callable $on_member, callable $on_vulnerabilities ) {
		$this->skip_whitespace();
		if ( "\xEF" === $this->peek() ) {
			$this->skip_bom();
			$this->skip_whitespace();
		}

		$found = false;
		$this->walk_object(
			function ( $key ) use ( $on_member, $on_vulnerabilities, &$found ) {
				if ( 'vulnerabilities' === $key && '{' === $this->peek() ) {
					$found = true;
					$this->walk_object(
						function ( $slug ) use ( $on_vulnerabilities ) {
							$on_vulnerabilities( $slug, $this->read_value() );
						}
					);
					return;
				}
				$on_member( $key, $this->read_value() );
			}
		);

		$this->skip_whitespace();
		if ( null !== $this->peek() ) {
			throw new UnexpectedValueException( 'The feed file has data after the closing "}".' );
		}

		return $found;
	}

	/**
	 * Walks the members of the object that starts at the read position.
	 *
	 * @param callable $visit Receives each member name, with the read position at
	 *                        its value, and must move past the value.
	 * @throws UnexpectedValueException When the input is not a JSON object.
	 */
	private function walk_object( callable $visit ) {
		if ( '{' !== $this->peek() ) {
			throw new UnexpectedValueException( 'The feed file is not a JSON object.' );
		}
		++$this->pos;

		$this->skip_whitespace();
		if ( '}' === $this->peek() ) {
			++$this->pos;
			return;
		}

		while ( true ) {
			$this->skip_whitespace();
			if ( '"' !== $this->peek() ) {
				throw new UnexpectedValueException( 'The feed file has a member without a name.' );
			}
			$this->mark = $this->pos;
			$this->scan_string();
			$key = json_decode( substr( $this->buffer, $this->mark, $this->pos - $this->mark ) );
			if ( ! is_string( $key ) ) {
				throw new UnexpectedValueException( 'The feed file has a member name that is not valid JSON.' );
			}

			$this->skip_whitespace();
			if ( ':' !== $this->peek() ) {
				throw new UnexpectedValueException( 'The feed file has a member without a value.' );
			}
			++$this->pos;
			$this->skip_whitespace();
			if ( null === $this->peek() ) {
				throw new UnexpectedValueException( 'The feed file ends in the middle of a member.' );
			}

			$visit( $key );

			$this->skip_whitespace();
			$next = $this->peek();
			if ( ',' === $next ) {
				++$this->pos;
				continue;
			}
			if ( '}' === $next ) {
				++$this->pos;
				return;
			}
			throw new UnexpectedValueException( 'The feed file has a member that is not followed by "," or "}".' );
		}
	}

	/**
	 * Returns the value that starts at the read position as raw JSON and moves past it.
	 *
	 * @return string
	 * @throws UnexpectedValueException When the value is not terminated.
	 */
	private function read_value() {
		$this->mark = $this->pos;
		$this->scan_value();
		$value      = substr( $this->buffer, $this->mark, $this->pos - $this->mark );
		$this->mark = $this->pos;
		return $value;
	}

	/**
	 * Reads more bytes, dropping what lies before $mark.
	 *
	 * @return bool False at the end of the stream.
	 * @throws UnexpectedValueException When the stream cannot be read.
	 */
	private function fill() {
		while ( ! $this->eof ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Streams a large file.
			$chunk = fread( $this->handle, $this->chunk_size );
			if ( false === $chunk ) {
				throw new UnexpectedValueException( 'The feed file could not be read.' );
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
	 * @throws UnexpectedValueException When the bytes are not a byte order mark.
	 */
	private function skip_bom() {
		$available = strlen( $this->buffer ) - $this->pos;
		while ( $available < 3 && $this->fill() ) {
			$available = strlen( $this->buffer ) - $this->pos;
		}
		if ( "\xEF\xBB\xBF" !== substr( $this->buffer, $this->pos, 3 ) ) {
			throw new UnexpectedValueException( 'The feed file is not a JSON object.' );
		}
		$this->pos += 3;
	}

	/**
	 * Moves past the string that starts at the read position.
	 *
	 * @throws UnexpectedValueException When the string is not terminated.
	 */
	private function scan_string() {
		++$this->pos;
		while ( true ) {
			$this->pos += strcspn( $this->buffer, '"\\', $this->pos );
			if ( $this->pos >= strlen( $this->buffer ) ) {
				if ( ! $this->fill() ) {
					throw new UnexpectedValueException( 'The feed file ends inside a string.' );
				}
				continue;
			}
			if ( '"' === $this->buffer[ $this->pos ] ) {
				++$this->pos;
				return;
			}
			// A backslash: the next byte is escaped, so it cannot end the string.
			if ( $this->pos + 1 >= strlen( $this->buffer ) && ! $this->fill() ) {
				throw new UnexpectedValueException( 'The feed file ends inside a string.' );
			}
			$this->pos += 2;
		}
	}

	/**
	 * Moves past the value that starts at the read position.
	 *
	 * @throws UnexpectedValueException When the value is not terminated.
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
						throw new UnexpectedValueException( 'The feed file ends inside a value.' );
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
