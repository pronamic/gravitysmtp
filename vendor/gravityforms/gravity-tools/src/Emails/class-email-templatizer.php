<?php

namespace Gravity_Forms\Gravity_Tools\Emails;

use Gravity_Forms\Gravity_Tools\Utils\Bettarray;

/**
 * Email_Templatizer
 *
 * For the given email markup, take find any placeholder tokens and replace them with values from
 * the provided $data. Can be used for any markup.
 */
class Email_Templatizer {

	protected $email_template = '';
	protected $open_delin;
	protected $close_delin;

	/**
	 * @param string $email_template The markup to parse
	 * @param string $open_delin     The string to use for the opening delimiter for tokens. Defaults to '{{'.
	 * @param string $close_delin    The string to use for the closing delimiter for tokens. Defaults to '}}'.
	 *
	 * @return void
	 */
	public function __construct( $email_template, $open_delin = '{{', $close_delin = '}}' ) {
		$this->email_template = $email_template;
		$this->open_delin     = $open_delin;
		$this->close_delin    = $close_delin;
	}

	/**
	 * Take the stored markup and render it with the given placeholder values.
	 *
	 * @param array|Bettarray $data       the data to use with the placeholders
	 * @param int             $max_length the maximum length, in characters, for the markup
	 * @param bool            $echo       whether to echo the markup (if false, markup will be returned)
	 *
	 * @return string|void
	 */
	public function render( $data, $max_length = false, $echo = false ) {
		// Cast to Bettarray to allow dot navigation.
		if ( ! is_a( $data, Bettarray::class ) ) {
			$data = new Bettarray( $data );
		}

		$rendered = $this->handle_loops( $data );
		$rendered = $this->handle_conditionals( $data, $rendered );
		$rendered = $this->handle_placeholders( $data, $rendered );

		if ( $max_length ) {
			$rendered = $this->truncate_markup( $rendered, $max_length );
		}

		if ( $echo ) {
			echo $rendered;

			return;
		}

		return $rendered;
	}

	private function handle_loops( $data, $markup = false ) {
		if ( ! $markup ) {
			$markup = $this->email_template;
		}

		$pattern = sprintf( '/(%s\|for[^\|]+\|%s)([^\|]+)(%s\|endfor\|%s)/', preg_quote( $this->open_delin ), preg_quote( $this->close_delin ), preg_quote( $this->open_delin ), preg_quote( $this->close_delin ) );

		return preg_replace_callback( $pattern, function ( $matches ) use ( $data ) {
			// Something has gone terribly awry, just return the original text.
			if ( count( $matches ) !== 4 ) {
				return $matches[0];
			}

			$opening  = $matches[1];
			$contents = $matches[2];

			$loop = str_replace( $this->open_delin . '|for', '', $opening );
			$loop = str_replace( '|' . $this->close_delin, '', $loop );
			$loop = trim( $loop );
			$loop_parts = explode( ' ', $loop );

			// Loop has invalid syntax. Bail.
			if ( count( $loop_parts ) !== 3 ) {
				return $matches[0];
			}

			$item_name = $loop_parts[0];
			$item_value_string = $loop_parts[2];

			$item_values = $data->get_raw( $item_value_string );

			if ( ! is_array( $item_values ) ) {
				return $matches[0];
			}

			$loop_data = new Bettarray( $data->all() );
			$loop_data->delete( $item_value_string );

			$return = '';

			foreach( $item_values as $item_value ) {
				$loop_data->set( $item_name, $item_value );;
				$template = new Email_Templatizer( $contents );
				$return .= $template->render( $loop_data );
			}

			return $return;
		}, $markup );

	}
	/**
	 * Process the markup to replace placeholders with data.
	 *
	 * @param Bettarray $data   the data containing the placeholder values
	 * @param string    $markup if passed, the markup to act upon
	 *
	 * @return string
	 */
	private function handle_placeholders( $data, $markup = false ) {
		if ( ! $markup ) {
			$markup = $this->email_template;
		}

		$pattern = sprintf( '/%s[^%s]*%s/', preg_quote( $this->open_delin ), preg_quote( $this->close_delin ), preg_quote( $this->close_delin ) );

		return preg_replace_callback( $pattern, function ( $matches ) use ( $data ) {
			$cleaned = str_replace( $this->open_delin, '', $matches[0] );
			$cleaned = str_replace( $this->close_delin, '', $cleaned );
			$search  = trim( $cleaned );

			$replacement = $data->get_raw( $search );

			if ( ! is_string( $replacement ) && ! is_numeric( $replacement ) ) {
				return $matches[0];
			}

			$sanitized = wp_kses_post( $replacement );

			return $sanitized;
		}, $markup );
	}

	private function handle_conditionals( $data, $markup = false ) {
		if ( ! $markup ) {
			$markup = $this->email_template;
		}

		$pattern = sprintf( '/(%s\|if[^\|]+\|%s)([^\|]+)(%s\|endif\|%s)/', preg_quote( $this->open_delin ), preg_quote( $this->close_delin ), preg_quote( $this->open_delin ), preg_quote( $this->close_delin ) );

		return preg_replace_callback( $pattern, function ( $matches ) use ( $data ) {
			// Something has gone terribly awry, just return the original text.
			if ( count( $matches ) !== 4 ) {
				return $matches[0];
			}

			$opening  = $matches[1];
			$contents = $matches[2];

			$condition = str_replace( $this->open_delin . '|if', '', $opening );
			$condition = str_replace( '|' . $this->close_delin, '', $condition );
			$condition = trim( $condition );

			$check_val = $data->get_raw( $condition );

			if ( empty( $check_val ) ) {
				return '';
			}

			return $contents;
		}, $markup );
	}

	/**
	 * A basic method to truncate the markup to $max_length characters.
	 *
	 * @todo Update to handle tags more gracefully to ensure none get truncated.
	 *
	 * @param string $markup     the markup to modify
	 * @param int    $max_length the number of characters to which the markup should be limited
	 *
	 * @return string
	 */
	private function truncate_markup( $markup, $max_length ) {
		return substr( $markup, 0, $max_length );
	}
}
