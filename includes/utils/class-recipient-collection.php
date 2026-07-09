<?php

namespace Gravity_Forms\Gravity_SMTP\Utils;

class Recipient_Collection {

	public $recipients = array();

	public function __construct( $recipients = array() ) {
		if ( empty( $recipients ) ) {
			return;
		}

		if ( is_string( $recipients ) ) {
			$recipients = array( $recipients );
		}

		foreach ( $recipients as $recipient ) {
			if ( is_string( $recipient ) ) {
				$recipientObj = new Recipient( $recipient, '' );
			} else {
				$name         = isset( $recipient['name'] ) ? $recipient['name'] : '';
				$recipientObj = new Recipient( $recipient['email'], $name );
			}
			$this->add( $recipientObj );
		}
	}

	public function add_raw( $email, $name ) {
		$recipient = new Recipient( $email, $name );

		$this->recipients[] = $recipient;
	}

	public function add( Recipient $recipient ) {
		$this->recipients[] = $recipient;
	}

	public function first() {
		if ( empty( $this->recipients ) ) {
			return new Recipient( '', '' );
		}

		return reset( $this->recipients );
	}

	public function recipients() {
		return $this->recipients;
	}

	public function count() {
		return count( $this->recipients );
	}

	public function as_array() {
		$return = array();

		foreach ( $this->recipients as $recipient ) {
			$return[] = $recipient->as_array();
		}

		return $return;
	}

	public function as_mailboxes() {
		$return = array();
		foreach ( $this->recipients as $recipient ) {
			$return[] = $recipient->mailbox();
		}

		return $return;
	}

	public function as_string( $mailbox = false ) {
		$items = array();

		foreach( $this->recipients as $recipient ) {
			$items[] = $mailbox ? $recipient->mailbox() : $recipient->email;
		}

		return implode( ',', $items );
	}

	/**
	 * Remove recipients whose email matches any in the given array.
	 *
	 * @since 2.3.0
	 *
	 * @param array $emails_to_remove Lowercase email addresses to remove.
	 *
	 * @return Recipient[] Array of removed Recipient objects.
	 */
	public function filter( array $emails_to_remove ) {
		$removed = array();

		$this->recipients = array_filter( $this->recipients, function( $recipient ) use ( $emails_to_remove, &$removed ) {
			if ( in_array( strtolower( $recipient->email ), $emails_to_remove, true ) ) {
				$removed[] = $recipient;
				return false;
			}
			return true;
		} );

		$this->recipients = array_values( $this->recipients );

		return $removed;
	}

}
