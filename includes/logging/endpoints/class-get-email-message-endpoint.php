<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Endpoints;

use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Get_Email_Message_Endpoint extends Endpoint {

	const PARAM_EVENT_ID = 'event_id';

	const ACTION_NAME = 'get_email_message';

	protected $minimum_cap = Roles::VIEW_EMAIL_LOG_DETAILS;

	/**
	 * @var Event_Model
	 */
	protected $emails;

	public function __construct( Event_Model $emails ) {
		$this->emails = $emails;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$event_id = filter_input( INPUT_GET, self::PARAM_EVENT_ID, FILTER_SANITIZE_NUMBER_INT );
		$email    = $this->emails->find( array( array( 'id', '=', $event_id ) ) );

		if ( empty( $email[0] ) ) {
			header( 'Content-Type: text/html' );
			/* translators: %d: email ID */
			echo sprintf( __( 'Could not get content for email ID: %d.', 'gravitysmtp' ), $email );
			wp_die();
		}

		header( 'Content-Type: text/html' );
		echo $this->format_email_content( $email[0]['message'] );
		wp_die();
	}

	protected function format_email_content( $content ) {
		if ( $content !== strip_tags( $content ) ) {
			// Open links in a new tab (target) and harden with rel=noopener noreferrer where needed.
			$content = preg_replace_callback(
				'/<a\s([^>]*?)>/i',
				function ( $matches ) {
					$attrs   = $matches[1];
					$trimmed = trim( $attrs );

					if ( '' === $trimmed ) {
						return $matches[0];
					}

					$attrs = $this->ensure_anchor_target_blank( $attrs );
					$attrs = $this->ensure_anchor_rel_noopener_noreferrer( $attrs );

					return '<a ' . trim( $attrs ) . '>';
				},
				$content
			);

			return $content;
		} else {
			return '<pre style="white-space: pre-wrap; word-break: break-all; color: #242748; padding: 20px 25px; font-size: 13px; font-family: inter, -apple-system, blinkmacsystemfont, \'Segoe UI\', roboto, oxygen-sans, ubuntu, cantarell, \'Helvetica Neue\';">' . htmlspecialchars( $content ) . '</pre><style>body { margin: 0; background: #fff; }</style>';
		}
	}

	/**
	 * Ensures anchor tags open in a new tab when attributes are present.
	 *
	 * @param string $attrs Raw attribute string from the opening &lt;a&gt; tag.
	 *
	 * @return string
	 */
	protected function ensure_anchor_target_blank( $attrs ) {
		if ( preg_match( '/\btarget\s*=\s*(["\']?)_blank\1/i', $attrs ) ) {
			return $attrs;
		}

		if ( preg_match( '/\btarget\s*=\s*("|\')(.*?)\1/is', $attrs ) ) {
			return preg_replace( '/\btarget\s*=\s*("|\')(.*?)\1/is', 'target="_blank"', $attrs, 1 );
		}

		if ( preg_match( '/\btarget\s*=\s*[^\s>]+/i', $attrs ) ) {
			return preg_replace( '/\btarget\s*=\s*[^\s>]+/i', 'target="_blank"', $attrs, 1 );
		}

		return rtrim( $attrs ) . ' target="_blank"';
	}

	/**
	 * Ensures rel includes noopener and noreferrer (safe external links with target _blank).
	 *
	 * @param string $attrs Raw attribute string from the opening &lt;a&gt; tag.
	 *
	 * @return string
	 */
	protected function ensure_anchor_rel_noopener_noreferrer( $attrs ) {
		$rel_quoted = '/\brel\s*=\s*(["\'])(.*?)\1/is';

		if ( preg_match( $rel_quoted, $attrs, $rel_match ) ) {
			$tokens = preg_split( '/\s+/', trim( $rel_match[2] ), -1, PREG_SPLIT_NO_EMPTY );

			if ( $this->rel_has_noopener_and_noreferrer( $tokens ) ) {
				return $attrs;
			}

			$tokens  = $this->merge_rel_noopener_noreferrer_tokens( $tokens );
			$new_rel = implode( ' ', $tokens );

			return preg_replace( $rel_quoted, 'rel="' . $new_rel . '"', $attrs, 1 );
		}

		if ( preg_match( '/\brel\s*=\s*([^\s>]+)/i', $attrs, $rel_match ) ) {
			$tokens = preg_split( '/\s+/', trim( $rel_match[1] ), -1, PREG_SPLIT_NO_EMPTY );

			if ( $this->rel_has_noopener_and_noreferrer( $tokens ) ) {
				return $attrs;
			}

			$tokens  = $this->merge_rel_noopener_noreferrer_tokens( $tokens );
			$new_rel = implode( ' ', $tokens );

			return preg_replace( '/\brel\s*=\s*[^\s>]+/i', 'rel="' . $new_rel . '"', $attrs, 1 );
		}

		return rtrim( $attrs ) . ' rel="noopener noreferrer"';
	}

	/**
	 * @param array $tokens rel attribute tokens.
	 *
	 * @return bool
	 */
	protected function rel_has_noopener_and_noreferrer( array $tokens ) {
		$lower = array_map( 'strtolower', $tokens );

		return in_array( 'noopener', $lower, true ) && in_array( 'noreferrer', $lower, true );
	}

	/**
	 * @param array $tokens rel attribute tokens.
	 *
	 * @return array
	 */
	protected function merge_rel_noopener_noreferrer_tokens( array $tokens ) {
		$lower = array_map( 'strtolower', $tokens );

		if ( ! in_array( 'noopener', $lower, true ) ) {
			$tokens[] = 'noopener';
		}

		if ( ! in_array( 'noreferrer', $lower, true ) ) {
			$tokens[] = 'noreferrer';
		}

		return $tokens;
	}

	protected function validate() {
		if ( ! parent::validate() ) {
			return false;
		}

		if ( empty( $_REQUEST[ self::PARAM_EVENT_ID ] ) ) {
			return false;
		}

		return true;
	}

}
