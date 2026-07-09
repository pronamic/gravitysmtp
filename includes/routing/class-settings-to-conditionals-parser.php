<?php

namespace Gravity_Forms\Gravity_SMTP\Routing;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Null;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Routing\Handlers\Conditional_Routing_Handler;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_SMTP\Utils\Header_Parser;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Parser;
use Gravity_Forms\Gravity_SMTP\Utils\Source_Parser;

class Settings_to_Conditionals_Parser {

	private $source_parser;

	public function __construct( Source_Parser $source_parser ) {
		$this->source_parser = $source_parser;
	}

	public function parse_email_data( $email_data ) {
		$connector = new Connector_Null( null, Gravity_SMTP::container()->get( Connector_Service_Provider::DATA_STORE_ROUTER ), null, null, new Header_Parser(), new Recipient_Parser(), null );
		$source_subtype = '';

		// Source can be explicitly defined in headers or the email data. Use those first, otherwise calculate the source.
		if ( is_array( $email_data['headers'] ) && isset( $email_data['headers']['source'] ) ) {
			$source = $email_data['headers']['source'];
		} elseif ( isset( $email_data['source'] ) || isset( $email_data['source_subtype'] ) ) {
			$source         = isset( $email_data['source'] ) ? $email_data['source'] : '';
			$source_subtype = isset( $email_data['source_subtype'] ) ? $email_data['source_subtype'] : 'wordpress';
		} else {
			$debug          = debug_backtrace();
			$source_data    = $this->source_parser->get_source_from_trace( $debug, 'all' );
			$source         = $source_data['slug'];
			$source_subtype = $source_data['subtype'];
		}

		$connector->init( $email_data['to'], $email_data['subject'], $email_data['message'], $email_data['headers'], $email_data['attachments'], $source );
		$parsed_data = $connector->get_email_data();

		$attachment_size = $this->calculate_attachment_size( $parsed_data['attachments'] );

		$data = array(
			'subject'           => $parsed_data['subject'],
			'message'           => $parsed_data['message'],
			'from_email'        => $parsed_data['from']['email'],
			'from_name'         => isset( $parsed_data['from']['name'] ) ? $parsed_data['from']['name'] : '',
			'to'                => isset( $parsed_data['to'] ) ? $parsed_data['to']->as_string() : '',
			'cc'                => isset( $parsed_data['headers']['cc'] ) ? $parsed_data['headers']['cc']->as_string() : '',
			'bcc'               => isset( $parsed_data['headers']['bcc'] ) ? $parsed_data['headers']['bcc']->as_string() : '',
			'reply_to'          => empty( $parsed_data['reply_to'] ) ? '' : $parsed_data['reply_to'][0]['email'],
			'source'            => $source,
			'to_count'          => isset( $parsed_data['to'] ) ? $parsed_data['to']->count() : 0,
			'cc_count'          => isset( $parsed_data['headers']['cc'] ) ? $parsed_data['headers']['cc']->count() : 0,
			'bcc_count'         => isset( $parsed_data['headers']['bcc'] ) ? $parsed_data['headers']['bcc']->count() : 0,
			'has_attachments'   => ! empty( $parsed_data['attachments'] ),
			'attachments'       => $parsed_data['attachments'],
			'attachments_count' => count( $parsed_data['attachments'] ),
			'attachment_size'   => $attachment_size,
			'content_type'      => strpos( $parsed_data['headers']['content-type'], 'html' ) !== false ? 'html' : 'text',
			'message_size'      => (int) strlen( $parsed_data['message'] ) / 1000,
			'source_subtype'    => $source_subtype,
		);

		foreach ( $parsed_data['headers'] as $name => $header ) {
			if ( $name === 'from' || isset( $data[ $name ] ) ) {
				continue;
			}

			$data[ $name ] = $header;
		}

		return $data;
	}

	private function calculate_attachment_size( $attachments ) {
		$sum = 0;

		foreach( $attachments as $filename => $attachment ) {
			// Explicitly-provided size. Calculate as KB and add.
			if ( isset( $attachment['size'] ) ) {
				$sum += floatval( (float) $attachment['size'] / 1000 );
				continue;
			}

			// Something is wrong with the attachment. Ignore.
			if ( is_array( $attachment ) ) {
				continue;
			}

			// Invalid file.
			if ( ! is_file( $attachment ) || ! is_readable( $attachment ) ) {
				continue;
			}

			$size = filesize( $attachment );

			// Add file size in KB
			$sum += floatval( $size / 1000 );
		}

		return $sum;
	}

	public function parse_settings( $settings, $email_data ) {
		$conditionals = array();

		foreach ( $settings as $data ) {
			if ( ! Booliesh::get( $data['enabled'] ) ) {
				continue;
			}

			$connector_name                  = $data['connector'];
			$connector_conditions            = $data['conditions'];
			$parent_group                    = new Conditional_Group( $connector_conditions['conjunct'] );
			$conditionals[]                  = array(
				'connector' => $connector_name,
				'rules'     => $this->recursively_parse_settings( $parent_group, $connector_conditions['rules'], $email_data ),
			);
		}

		return $conditionals;
	}

	public static function recursively_fix_stringified_bools( $values ) {
		$fixed = array();
		foreach( $values as $key => $value ) {
			if ( is_array( $value ) ) {
				$fixed[ $key ] = self::recursively_fix_stringified_bools( $value );
				continue;
			}

			if ( $value === 'false' ) {
				$fixed[ $key ] = false;
				continue;
			}

			if ( $value === 'true' ) {
				$fixed[ $key ] = true;
				continue;
			}

			$fixed[ $key ] = $value;
		}

		return $fixed;
	}


	private function recursively_parse_settings( $parent_group, $rules, $email_data ) {
		foreach ( $rules as $rule ) {
			if ( array_key_exists( 'conjunct', $rule ) ) {
				$sub_parent = new Conditional_Group( $rule['conjunct'] );
				$parent_group->add_condition( $this->recursively_parse_settings( $sub_parent, $rule['rules'], $email_data ) );
				continue;
			}

			$callback = array( Conditional_Routing_Handler::class, 'email_data_callback' );

			$args     = array(
				$rule['email_field'],
				$rule['value'],
				$rule['comparator'],
				$email_data,
				isset( $rule['regex'] ) ? Booliesh::get( $rule['regex'] ) : false,
			);

			$parent_group->add_condition( new Callback_Conditional( $callback, $args ) );
		}

		return $parent_group;
	}
}
