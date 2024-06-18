<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors;

use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Logging\Log\Logger;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Utils\Header_Parser;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Parser;

/**
 * Connector_Base
 *
 * The base class for any connector registered to the system. Handles defining sending logic,
 * settings fields for the connector, and data handling.
 *
 * @since 1.0
 */
abstract class Connector_Base {

	const SETTING_ENABLED          = 'enabled';
	const SETTING_ACTIVATED        = 'activated';
	const SETTING_CONFIGURED       = 'configured';
	const SETTING_FROM_EMAIL       = 'from_email';
	const SETTING_FROM_NAME        = 'from_name';
	const SETTING_FORCE_FROM_EMAIL = 'force_from_email';
	const SETTING_FORCE_FROM_NAME  = 'force_from_name';
	const SETTING_IS_PRIMARY       = 'is_primary';
	const SETTING_IS_BACKUP        = 'is_backup';

	protected static $configured = null;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $logo;

	/**
	 * @var string
	 */
	protected $full_logo;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var bool
	 */
	protected $disabled;

	/**
	 * @var \PHPMailer
	 */
	protected $php_mailer;

	/**
	 * @var Data_Store_Router $data_store
	 */
	protected $data_store;

	/**
	 * @var Logger $logger
	 */
	protected $logger;

	/**
	 * @var Event_Model $emails
	 */
	protected $emails;

	/**
	 * @var array
	 */
	protected $events;

	/**
	 * @var array
	 */
	protected $atts;

	/**
	 * @var Header_Parser
	 */
	protected $header_parser;

	/**
	 * @var Recipient_Parser
	 */
	protected $recipient_parser;

	/**
	 * Calls to wp_mail() will be routed to this method if this connector is enabled. Parameters
	 * are a match for wp_mail().
	 *
	 * @since 1.0
	 *
	 * @return mixed
	 */
	abstract public function send();

	/**
	 * Define the settings fields for this connector, matching the field types and props to those in
	 * the React components.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	abstract public function settings_fields();

	/**
	 * Define the data that should be saved and loaded for this connector when dealing with its
	 * functionality. Typically this will be the values being modified via the Settings Fields, but
	 * can include other data as well.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	abstract public function connector_data();

	/**
	 * Get the description for this connector. Override in each connector to translate.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Define any i18n strings needed for this connector. Defaults to noop.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function connector_i18n() {
		return array();
	}

	/**
	 * A map to handle migrating existing settings to this connector. Should
	 * return an array of arrays containing the following values:
	 *
	 * - original_key: The key for the setting in the existing add-on.
	 *
	 * - new_key:      The new key to map the value to in our system.
	 *
	 * - sub_key:      The optional sub-key to search for in a multidimensional array of
	 *                 options. Arrays can be navigated using '/', e.g. 'key/subkey/subsubkey'.
	 *
	 * - transform:    An optional callback to apply to the value before saving.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function migration_map() {
		return array();
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param $php_mailer
	 * @param $data_store
	 * @param $logger
	 * @param $events
	 * @param $header_parser
	 *
	 * @return void
	 */
	public function __construct( $php_mailer, $data_store, $logger, $events, $header_parser, $recipient_parser ) {
		$this->php_mailer    = $php_mailer;
		$this->data_store    = $data_store;
		$this->logger        = $logger;
		$this->events        = $events;
		$this->header_parser = $header_parser;
		$this->recipient_parser = $recipient_parser;
	}

	/**
	 * Initialize the connector and map attributes as necessary.
	 *
	 * @since 1.0
	 *
	 * @param $to
	 * @param $subject
	 * @param $message
	 * @param $headers
	 * @param $attachments
	 *
	 * @return void
	 */
	public function init( $to, $subject, $message, $headers = '', $attachments = array(), $source = '' ) {
		$to = $this->recipient_parser->parse( $to );

		$parsed_headers = $this->get_parsed_headers( $headers );

		if ( isset( $parsed_headers['from'] ) ) {
			$from_data = $this->get_email_from_header( 'From', $parsed_headers['from'] );
			$from      = $from_data->recipients()[0]->email();
			$from_name = $from_data->recipients()[0]->name();
		} else {
			$from      = '';
			$from_name = '';
		}

		/**
		 * Filters the wp_mail() arguments.
		 *
		 * @since 2.2.0
		 *
		 * @param array $args A compacted array of wp_mail() arguments, including the "to" email,
		 *                    subject, message, headers, and attachments values.
		 */
		$this->atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments', 'from', 'from_name', 'source' ) );
	}

	/**
	 * Get an attribute, passing it through necessary filters.
	 *
	 * @since 1.0
	 *
	 * @param $att_name
	 * @param $default
	 *
	 * @return mixed|null
	 */
	public function get_att( $att_name, $default = '' ) {
		$value           = isset( $this->atts[ $att_name ] ) ? $this->atts[ $att_name ] : $default;
		$wp_mail_filters = array( 'from', 'from_name', 'content_type', 'charset' );

		if ( in_array( $att_name, $wp_mail_filters ) ) {
			$value = apply_filters( 'wp_mail_' . $att_name, $value );
		}

		return apply_filters( 'gravitysmtp_email_attribute_' . $att_name, $value );
	}

	/**
	 * Get the From email.
	 *
	 * @since 1.0
	 *
	 * @param bool $return_array Wether to return an array containing the individual parts of the from address ( 'email', 'name' and 'from') or just the From string.
	 *
	 * @return string | array
	 */
	protected function get_from( $return_array = false ) {
		$force_from_email = $this->get_setting( self::SETTING_FORCE_FROM_EMAIL, false );
		$force_from_name  = $this->get_setting( self::SETTING_FORCE_FROM_NAME, false );

		if ( empty( $force_from_email ) && empty( $force_from_name ) ) {
			$from = $this->get_att( 'from', '' );
			if ( empty( $from ) ) {
				$from = $this->get_setting( self::SETTING_FROM_EMAIL, '' );
			}

			$from_name = $this->get_att( 'from_name', '' );
			if ( empty( $from_name ) ) {
				$from_name = $this->get_setting( self::SETTING_FROM_NAME, '' );
			}
		} else {
			$from = ! empty( $force_from_email )
				? $this->get_setting( self::SETTING_FROM_EMAIL, '' )
				: $this->get_att( 'from', '' );

			$from_name = ! empty( $force_from_name )
				? $this->get_setting( self::SETTING_FROM_NAME, '' )
				: $this->get_att( 'from_name', '' );
		}

		$from_str = ! empty( $from_name ) ? $from_name . ' <' . $from . '>' : $from;

		if ( $return_array ) {
			$return = array(
				'email' => $from,
				'from'  => $from_str,
			);

			if ( ! empty( $from_name ) ) {
				$return['name'] = $from_name;
			}

			return $return;
		}

		return $from_str;
	}

	public function get_request_params() {
		return array();
	}

	/**
	 * Get the default From settings fields.
	 *
	 * @since 1.0
	 *
	 * @return array[] Returns an array of settings fields.
	 */
	protected function get_from_settings_fields() {
		return array(
			array(
				'component' => 'Input',
				'props'     => array(
					'labelAttributes' => array(
						'label'  => esc_html__( 'Default From Email', 'gravitysmtp' ),
						'size'   => 'text-sm',
						'weight' => 'medium',
					),
					'name'            => self::SETTING_FROM_EMAIL,
					'spacing'         => 6,
					'size'            => 'size-l',
					'value'           => $this->get_setting( self::SETTING_FROM_EMAIL, '' ),
				),
			),
			array(
				'component' => 'Toggle',
				'props'     => array(
					'initialChecked'     => (bool) $this->get_setting( self::SETTING_FORCE_FROM_EMAIL, false ),
					'labelAttributes'    => array(
						'label' => esc_html__( 'Force From Email', 'gravitysmtp' ),
					),
					'helpTextAttributes' => array(
						'content' => esc_html__( 'If Force Email is enabled, the Default From Email address will override other plugin settings for all outgoing emails.', 'gravitysmtp' ),
						'size'    => 'text-xs',
						'spacing' => array( 2, 0, 0, 0 ),
						'weight'  => 'regular',
					),
					'helpTextWidth'      => 'full',
					'labelPosition'      => 'left',
					'name'               => self::SETTING_FORCE_FROM_EMAIL,
					'size'               => 'size-m',
					'spacing'            => 6,
					'width'              => 'full',
				),
			),
			array(
				'component' => 'Input',
				'props'     => array(
					'labelAttributes' => array(
						'label'  => esc_html__( 'Default From Name', 'gravitysmtp' ),
						'size'   => 'text-sm',
						'weight' => 'medium',
					),
					'name'            => self::SETTING_FROM_NAME,
					'size'            => 'size-l',
					'spacing'         => 6,
					'value'           => $this->get_setting( self::SETTING_FROM_NAME, '' ),
				),
			),
			array(
				'component' => 'Toggle',
				'props'     => array(
					'initialChecked'     => (bool) $this->get_setting( self::SETTING_FORCE_FROM_NAME, false ),
					'labelAttributes'    => array(
						'label' => esc_html__( 'Force From Name', 'gravitysmtp' ),
					),
					'helpTextAttributes' => array(
						'content' => esc_html__( 'If Force Name is enabled, the Default From Name will override other plugin settings for all outgoing emails.', 'gravitysmtp' ),
						'size'    => 'text-xs',
						'spacing' => array( 2, 0, 0, 0 ),
						'weight'  => 'regular',
					),
					'helpTextWidth'      => 'full',
					'labelPosition'      => 'left',
					'name'               => self::SETTING_FORCE_FROM_NAME,
					'size'               => 'size-m',
					'spacing'            => 6,
					'width'              => 'full',
				),
			),
		);
	}

	public function get_reply_to( $return_as_array = false ) {
		$parsed_headers = $this->get_parsed_headers( $this->atts['headers'] );

		if ( ! isset( $parsed_headers['reply-to'] ) ) {
			return $return_as_array ? array() : '';
		}

		$email_data = $this->get_email_from_header( 'Reply-To', $parsed_headers['reply-to'] );

		return $return_as_array ? $email_data->as_array() : $email_data->as_string();
	}

	/**
	 * Retrieve any additional message headers that may have been added, whether through filters or
	 * custom code.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_filtered_message_headers() {
		$headers = $this->get_parsed_headers( $this->get_att( 'headers', array() ) );

		foreach( $this->header_parser->standard_headers as $header ) {
			unset( $headers[ $header ] );
		}

		return $headers;
	}

	/**
	 * Get parsed/normalized headers for use in PHP Mailer.
	 *
	 * @since 1.0
	 *
	 * @param $headers
	 *
	 * @return array
	 */
	protected function get_parsed_headers( $headers ) {
		$parsed_headers = $this->header_parser->parse( $headers );

		if ( ! isset( $parsed_headers['content-type'] ) ) {
			$parsed_headers['content-type'] = 'text/plain';
		}

		return $parsed_headers;
	}

	protected function get_header_from_string( $string ) {
		return $this->header_parser->get_header_from_string( $string );
	}

	protected function get_formatted_cc( $values ) {
		return $this->header_parser->get_formatted_cc( $values );
	}

	/**
	 * Get the email address info from the Header strings.
	 *
	 * @sicne 1.0
	 *
	 * @param $header_name
	 * @param $header_string
	 *
	 * @return array|array[]
	 */
	protected function get_email_from_header( $header_name, $header_string ) {
		return $this->header_parser->get_email_from_header( $header_name, $header_string );
	}

	/**
	 * Helper method for retrieving plugin settings (i.e., settings for the plugin globally
	 * and not specific to this connector).
	 *
	 * @since 1.0
	 *
	 * @param $setting_name
	 * @param $default
	 *
	 * @return mixed|null
	 */
	protected function get_plugin_setting( $setting_name, $default = null ) {
		return $this->data_store->get_plugin_setting( $setting_name, $default );
	}

	/**
	 * Helper method to retrieve a saved setting specifically for this connector.
	 *
	 * @since 1.0
	 *
	 * @param $setting_name
	 * @param $default
	 *
	 * @return mixed
	 */
	protected function get_setting( $setting_name, $default = null ) {
		return $this->data_store->get_setting( $this->name, $setting_name, $default );
	}

	/**
	 * Get the mapped data for this connector to use in a Config.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_data() {
		return array(
			'fields'      => $this->settings_fields(),
			'data'        => $this->get_merged_data(),
			'i18n'        => $this->connector_i18n(),
			'name'        => $this->name,
			'logo'        => $this->logo,
			'full_logo'   => $this->full_logo,
			'title'       => $this->title,
			'description' => $this->get_description(),
		);
	}

	/**
	 * Get the unique data for this connector, merged with the default/common data for all
	 * connectors in the system.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function get_merged_data() {
		// @todo - we might want to refactor this to use the Cache class in the future.
		$configured_key = sprintf( 'gsmtp_connector_configured_%s', $this->name );
		$cached = get_transient( $configured_key );

		if ( $cached === false ) {
			$is_configured = $this->is_configured();
			$configured = ( ! is_wp_error( $is_configured ) && $is_configured !== false );
			set_transient( $configured_key, array( 'configured' => $configured ), DAY_IN_SECONDS );
		} else {
			$configured = $cached['configured'];
		}

		$defaults = array(
			self::SETTING_ACTIVATED  => $this->get_setting( self::SETTING_ACTIVATED, true ),
			self::SETTING_CONFIGURED => $configured,
			self::SETTING_ENABLED    => $this->get_setting( self::SETTING_ENABLED, false ),
			self::SETTING_IS_PRIMARY => $this->get_setting( self::SETTING_IS_PRIMARY, false ),
			self::SETTING_IS_BACKUP  => $this->get_setting( self::SETTING_IS_BACKUP, false ),
		);

		return array_merge( $this->connector_data(), $defaults );
	}

	/**
	 * Whether this connector has been configured by the user. Defaults to checking for stored
	 * settings values, but can be overridden for other logic.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_configured() {
		return false;
	}

	/**
	 * Whether test mode setting is enabled for the plugin.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	protected function is_test_mode() {
		$test_mode = $this->get_plugin_setting( 'test_mode', 'false' );

		if ( empty( $test_mode ) ) {
			$test_mode = false;
		} else {
			$test_mode = $test_mode !== 'false';
		}

		return $test_mode;
	}
}
