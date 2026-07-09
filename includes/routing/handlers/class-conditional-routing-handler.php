<?php

namespace Gravity_Forms\Gravity_SMTP\Routing\Handlers;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Enums\Connector_Status_Enum;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;

class Conditional_Routing_Handler implements Routing_Handler {

	const EQ        = '=';
	const NEQ       = '!=';
	const GT        = '>';
	const GTE       = '>=';
	const LT        = '<';
	const LTE       = '<=';
	const CONTAINS  = 'contains';
	const DNCONTAIN = 'does_not_contain';
	const SW        = 'starts_with';
	const EW        = 'ends_with';

	private $conditionals = array();

	private $rule_selected = false;

	private $data_router;
	private $logger;

	private static $primary_attempted = false;
	private static $backup_attempted  = false;

	private static $enabled_count = null;

	public function __construct( $data_router, $logger ) {
		$this->data_router = $data_router;
		$this->logger = $logger;
	}

	public function set_conditionals( $conditionals ) {
		$this->conditionals = $conditionals;
	}

	public function reset() {
		self::$primary_attempted = false;
		self::$backup_attempted = false;
	}

	public function handle( $current_connector, $email_data ) {
		if ( self::$backup_attempted ) {
			$this->logger->log_warning( $this->error_prefix() . __( 'Routed and Backup integrations failed to send. Aborting email send.', 'gravitysmtp' ) );
			return false;
		}

		if ( self::$primary_attempted ) {
			self::$backup_attempted = true;
			$type                   = $this->data_router->get_connector_status_of_type( Connector_Status_Enum::BACKUP );

			if ( ! $type ) {
				$this->logger->log_warning( $this->error_prefix() . __( 'Backup integration not set, aborting email send.', 'gravitysmtp' ) );
				return false;
			}

			$enabled = $this->data_router->get_setting( $type, Connector_Base::SETTING_ENABLED, false );

			/* translators: %1$s: integration type */
			$this->logger->log_debug( $this->error_prefix() . sprintf( __( 'Backup integration identified: %1$s', 'gravitysmtp' ), $type ) );

			if ( $enabled ) {
				return $type;
			}

			$this->logger->log_debug( $this->error_prefix() . __( 'Backup integration not enabled. Skipping.', 'gravitysmtp' ) );

			return false;
		}

		// Routing between integrations is meaningless with fewer than two active; the UI
		// locks rules out in that state, so sending must ignore them too.
		if ( $this->active_connector_count() < 2 ) {
			return $current_connector;
		}

		foreach ( $this->conditionals as $index => $conditional_group ) {
			$connector = $conditional_group['connector'];

			if ( ! Booliesh::get( $this->data_router->get_setting( $connector, Connector_Base::SETTING_ENABLED, false ) ) ) {
				continue;
			}

			$conditional = $conditional_group['rules'];

			if ( $conditional->resolve() ) {
				self::$primary_attempted = true;
				$this->rule_selected = $index;

				$this->logger->log_debug( $this->error_prefix() . sprintf( __( 'Routed rule matched: %1$s; Connector selected: %2$s', 'gravitysmtp' ), $index, $connector ) );
				return $connector;
			}
		}

		return $current_connector;
	}

	private function active_connector_count() {
		if ( ! is_null( self::$enabled_count ) ) {
			return self::$enabled_count;
		}

		$count      = 0;
		$registered = Gravity_SMTP::container()->get( Connector_Service_Provider::REGISTERED_CONNECTORS );

		foreach ( array_keys( $registered ) as $connector ) {
			$enabled = $this->data_router->get_setting( $connector, Connector_Base::SETTING_ENABLED, false );

			if ( Booliesh::get( $enabled ) ) {
				$count++;
			}

			if ( $count > 2 ) {
				break;
			}
		}

		self::$enabled_count = $count;

		return $count;
	}

	public function get_selected_rule() {
		return $this->rule_selected;
	}

	private function error_prefix() {
		return sprintf( '%s:: ', str_replace( __NAMESPACE__, '', __CLASS__ ) );
	}

	public static function email_data_callback( $email_field, $value, $comparator, $email_data, $is_regex ): bool {
		if ( ! isset( $email_data[ $email_field ] ) ) {
			return false;
		}

		$comparison_value = $email_data[ $email_field ];

		switch ( $comparator ) {
			case self::EQ:
				return $comparison_value == $value;

			case self::NEQ:
				return $comparison_value != $value;

			case self::GT:
				return $comparison_value > $value;

			case self::GTE:
				return $comparison_value >= $value;

			case self::LT:
				return $comparison_value < $value;

			case self::LTE:
				return $comparison_value <= $value;

			case self::CONTAINS:
				if ( $value === null || $value === '' ) {
					return false;
				}

				return $is_regex ? ( preg_match( '/' . $value . '/', $comparison_value ) !== 0 && preg_match( '/' . $value . '/', $comparison_value ) !== false ) : strpos( $comparison_value, $value ) !== false;

			case self::DNCONTAIN:
				if ( $value === null || $value === '' ) {
					return false;
				}

				$contains = $is_regex ? ( preg_match( '/' . $value . '/', $comparison_value ) !== 0 && preg_match( '/' . $value . '/', $comparison_value ) !== false ) : strpos( $comparison_value, $value ) !== false;

				return ! $contains;

			case self::SW:
				$value = (string) $value;

				if ( $value !== '0' && empty( $value ) ) {
					return false;
				}

				return strpos( $comparison_value, $value ) === 0;

			case self::EW:
				$value = (string) $value;

				if ( $value !== '0' && empty( $value ) ) {
					return false;
				}

				return self::ends_with( $comparison_value, $value );

			default:
				return false;
		}
	}

	private static function ends_with( $string, $substr ) {
		$strlen  = strlen( $string );
		$testlen = strlen( $substr );

		if ( $testlen > $strlen ) {
			return false;
		}

		return substr_compare( $string, $substr, $strlen - $testlen, $testlen ) === 0;
	}
}
