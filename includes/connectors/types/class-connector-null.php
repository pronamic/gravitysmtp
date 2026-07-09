<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;

class Connector_Null extends Connector_Base {

	public function get_email_data() {
		$headers = $this->get_parsed_headers( $this->get_att( 'headers', array() ) );

		if ( ! empty( $headers['content-type'] ) ) {
			$headers['content-type'] = $this->get_att( 'content_type', $headers['content-type'] );
		}

		return array(
			'to'          => $this->get_att( 'to', '' ),
			'subject'     => $this->get_att( 'subject', '' ),
			'message'     => $this->get_att( 'message', '' ),
			'headers'     => $headers,
			'attachments' => $this->get_att( 'attachments', array() ),
			'from'        => $this->get_from( true ),
			'reply_to'    => $this->get_reply_to( true ),
			'source'      => $this->get_att( 'source', '' ),
		);
	}

	public function send() {
		return;
	}

	public function settings_fields() {
		return array();
	}

	public function connector_data() {
		return array();
	}

	public function init($to, $subject, $message, $headers = '', $attachments = array(), $source = '')
	{
		$service_name = $this->name === 'phpmail' ? 'wp_mail' : $this->name;

		// Set to blank values to avoid warnings.
		$from      = '';
		$from_name = '';
		/**
		 * Filters the wp_mail() arguments.
		 *
		 * @since 2.2.0
		 *
		 * @param array $args A compacted array of wp_mail() arguments, including the "to" email,
		 *                    subject, message, headers, and attachments values.
		 */
		$atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments', 'from', 'from_name', 'source' ) );

		$atts['to'] = $this->recipient_parser->parse( $atts['to'] );

		$parsed_headers = $this->get_parsed_headers( $atts['headers'] );

		if ( isset( $parsed_headers['from'] ) ) {
			$from_data = $this->get_email_from_header( 'From', $parsed_headers['from'] );
			$atts['from']      = $from_data->recipients()[0]->email();
			$atts['from_name'] = $from_data->recipients()[0]->name();
		} else {
			$atts['from']      = '';
			$atts['from_name'] = '';
		}

		$this->atts = $atts;

		do_action( 'gravitysmtp_after_connector_init', $this->email, $this );
	}
}
