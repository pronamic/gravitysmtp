<?php

namespace Gravity_Forms\Gravity_SMTP\Routing\Config;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Enums\Connector_Status_Enum;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Routing\Endpoints\Save_Routing_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Routing\Settings_to_Conditionals_Parser;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_Tools\Config;

class Routing_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function should_enqueue() {
		if ( ! is_admin() || ! current_user_can( Roles::VIEW_ROUTING ) ) {
			return false;
		}

		$page = filter_input( INPUT_GET, 'page' );

		return is_string( $page ) && htmlspecialchars( $page ) === 'gravitysmtp-settings';
	}

	public function data() {
		return array(
			'components' => array(
				'settings' => array(
					'i18n' => array(
						'routing' => $this->i18n_values(),
					),
					'data' => array(
						'routing_settings' => $this->data_values(),
					),
				),
			),
		);
	}

	private function i18n_values() {
		return array(
			'add_condition_button_label'       => __( 'And', 'gravitysmtp' ),
			'add_new_condition_button_label'   => __( 'Add New Condition', 'gravitysmtp' ),
			'add_new_routing_button_label'     => __( 'Add New Rule', 'gravitysmtp' ),
			'and_label'                        => __( 'and', 'gravitysmtp' ),
			'cancel_button_label'              => __( 'Cancel', 'gravitysmtp' ),
			'choose_provider_label'            => __( 'Choose Provider', 'gravitysmtp' ),
			'comparator_label'                 => __( 'Comparator', 'gravitysmtp' ),
			'condition_field_label'            => __( 'Condition field', 'gravitysmtp' ),
			/* translators: %s: a condition value that is matched as a regular expression. */
			'condition_regex_value'            => __( '%s (regex)', 'gravitysmtp' ),
			'delete_button_label'              => __( 'Delete', 'gravitysmtp' ),
			'delete_dialog_confirm_label'      => __( 'Delete', 'gravitysmtp' ),
			'delete_dialog_content'            => __( 'Are you sure you want to delete this rule? This action cannot be undone.', 'gravitysmtp' ),
			'delete_dialog_title'              => __( 'Delete Rule', 'gravitysmtp' ),
			'dropdown_search_placeholder'      => __( 'Search', 'gravitysmtp' ),
			/* translators: {{provider}} tags are replaced by a select input for the provider. */
			'edit_conditions_intro'            => __( 'Send with {{provider}}{{provider}} if the following conditions are met:', 'gravitysmtp' ),
			'edit_routing_button_label'        => __( 'Edit Rule', 'gravitysmtp' ),
			'enabled_toggle_label'             => __( 'Enable rule', 'gravitysmtp' ),
			'fallback_recipe_name'             => __( 'Rule', 'gravitysmtp' ),
			/* translators: %s: name of an integration that is no longer configured and active. */
			'invalid_provider_option_label'    => __( '%s (unavailable)', 'gravitysmtp' ),
			'invalid_regex_error'              => __( 'Invalid regular expression.', 'gravitysmtp' ),
			'invalid_rule_badge_label'         => __( 'Invalid', 'gravitysmtp' ),
			'invalid_rules_alert'              => __( 'One or more routing rules are invalid because their sending integration is no longer configured and active. Please review the highlighted rules below and choose an available integration.', 'gravitysmtp' ),
			/* translators: %s: an email source (plugin, theme, or WordPress) that is no longer installed. */
			'invalid_source_option_label'      => __( '%s (unavailable)', 'gravitysmtp' ),
			'move_rule_down_label'             => __( 'Move rule down.', 'gravitysmtp' ),
			'move_rule_up_label'               => __( 'Move rule up.', 'gravitysmtp' ),
			'or_label'                         => __( 'or', 'gravitysmtp' ),
			'preset_add_button_label'          => __( 'Add This Preset', 'gravitysmtp' ),
			'presets_button_label'             => __( 'Add from Preset', 'gravitysmtp' ),
			'presets_dialog_heading'           => __( 'Add a Preset Rule', 'gravitysmtp' ),
			'presets_heading'                  => __( 'Start With a Preset', 'gravitysmtp' ),
			/* translators: {{provider}} tags are replaced by a text component for the provider. */
			'preview_conditions_intro'         => __( 'Send with {{provider}}{{provider}} if', 'gravitysmtp' ),
			'recipe_name_label'                => __( 'Rule name', 'gravitysmtp' ),
			'regex_toggle_label'               => __( 'Use Regex', 'gravitysmtp' ),
			'routing_unavailable_message'      => __( 'You must have at least 2 integrations configured and active to use email routing.', 'gravitysmtp' ),
			'routing_unavailable_rules_note'   => __( 'Your existing rules have been disabled and cannot be enabled or edited until at least 2 integrations are configured and active. They will be restored automatically.', 'gravitysmtp' ),
			/* translators: %2$s: the rule's new list position. */
			'rule_moved_announcement'          => __( 'Rule moved to position %2$s.', 'gravitysmtp' ),
			'save_settings_button_label'       => __( 'Save Rule', 'gravitysmtp' ),
			'snackbar_preset_add_success'      => __( 'Routing preset added.', 'gravitysmtp' ),
			'source_group_mu_plugins'          => __( 'MU Plugins', 'gravitysmtp' ),
			'source_group_plugins'             => __( 'Plugins', 'gravitysmtp' ),
			'source_group_themes'              => __( 'Themes', 'gravitysmtp' ),
			'snackbar_delete_error'            => __( 'There was an error deleting the rule.', 'gravitysmtp' ),
			'snackbar_delete_success'          => __( 'Rule deleted.', 'gravitysmtp' ),
			'snackbar_save_error'              => __( 'There was an error saving routing settings.', 'gravitysmtp' ),
			'snackbar_save_success'            => __( 'Routing settings saved.', 'gravitysmtp' ),
			'test_email_routing_button_label'  => __( 'Test Email Routing', 'gravitysmtp' ),
			'test_modal_aria_collapse'         => __( 'Collapse section', 'gravitysmtp' ),
			'test_modal_aria_expand'           => __( 'Expand section', 'gravitysmtp' ),
			'test_modal_cancel_label'          => __( 'Cancel', 'gravitysmtp' ),
			'test_modal_close_label'           => __( 'Close', 'gravitysmtp' ),
			'test_modal_description'           => __( 'Fill in only the fields you want to test. Blank fields use the same defaults as a real email. The test runs against all enabled routing rules as shown on this screen, including unsaved changes.', 'gravitysmtp' ),
			'test_modal_group_attachments'     => __( 'Attachments', 'gravitysmtp' ),
			'test_modal_group_content'         => __( 'Content', 'gravitysmtp' ),
			'test_modal_group_other'           => __( 'Other', 'gravitysmtp' ),
			'test_modal_group_recipients'      => __( 'Recipients', 'gravitysmtp' ),
			'test_modal_group_sender'          => __( 'Sender', 'gravitysmtp' ),
			'test_modal_group_source'          => __( 'Source', 'gravitysmtp' ),
			'test_modal_result_error'          => __( 'There was an error testing the routing. Please try again.', 'gravitysmtp' ),
			'test_modal_result_match_heading'  => __( 'Match found', 'gravitysmtp' ),
			/* translators: %1$s: routing rule name, %2$s: connector name. */
			'test_modal_result_match_message'  => __( '%1$s matched. This email would send with %2$s.', 'gravitysmtp' ),
			'test_modal_result_no_match_heading' => __( 'No match', 'gravitysmtp' ),
			'test_modal_result_no_match_message' => __( 'No routing rule matched. This email would send with your default provider.', 'gravitysmtp' ),
			'test_modal_select_any_label'      => __( 'Any', 'gravitysmtp' ),
			'test_modal_send_label'            => __( 'Run Test', 'gravitysmtp' ),
			'test_modal_test_again_label'      => __( 'Test Again', 'gravitysmtp' ),
			'test_modal_title'                 => __( 'Test Email Routing', 'gravitysmtp' ),
			/* translators: {{docs_link}} tags are replaced by opening and closing tags for a link to our email routing documentation. */
			'top_content'                      => __( 'Create conditional routing rules to choose which provider sends each email. Rules are evaluated top to bottom and the first matching rule is used. {{docs_link}}Learn more about email routing.{{docs_link}}', 'gravitysmtp' ),
			'top_heading'                      => __( 'Email Routing', 'gravitysmtp' ),
			'value_label'                      => __( 'Value', 'gravitysmtp' ),
			'value_required_error'             => __( 'Value is required.', 'gravitysmtp' ),
		);
	}

	private function data_values() {
		$container         = Gravity_SMTP::container();
		$plugin_data_store = $container->get( Connector_Service_Provider::DATA_STORE_PLUGIN_OPTS );
		$settings          = $plugin_data_store->get( Save_Routing_Settings_Endpoint::PARAM_SETTINGS, 'config', array() );

		return array(
			'comparator_options' => $this->comparator_options(),
			'field_options'      => $this->field_options(),
			'provider_labels'    => $this->provider_labels(),
			'provider_options'   => $this->provider_options(),
			'source_options'     => $this->source_options(),
			'presets'            => $this->preset_recipes(),
			'recipes'            => is_array( $settings ) ? $this->normalize_recipes( $settings ) : array(),
		);
	}

	private function preset_recipes() {
		$connector   = $this->preset_connector();
		$admin_email = sanitize_email( get_option( 'admin_email', '' ) );

		$presets = array(
			$this->preset_recipe(
				'admin_notifications',
				__( 'Admin Notifications', 'gravitysmtp' ),
				$this->or_conditions( array(
					$this->and_conditions( array(
						$this->condition( 'source_subtype', '=', 'core' ),
						$this->condition( 'from_email', 'contains', $admin_email ),
					) ),
				) ),
				$connector
			),
			$this->preset_recipe(
				'handle_large_emails',
				__( 'Handle Large Emails', 'gravitysmtp' ),
				$this->or_conditions( array(
					$this->and_conditions( array(
						$this->condition( 'message_size', '>', '500' ),
					) ),
					$this->and_conditions( array(
						$this->condition( 'attachment_size', '>', '1000' ),
					) ),
				) ),
				$connector
			),
			$this->preset_recipe(
				'high_volume_notification',
				__( 'High-Volume Notification', 'gravitysmtp' ),
				$this->or_conditions( array(
					$this->and_conditions( array(
						$this->condition( 'to_count', '>', '5' ),
					) ),
				) ),
				$connector
			),
		);

		if ( class_exists( 'GFForms' ) ) {
			$presets[] = $this->preset_recipe(
				'gravity_forms_form_submissions_with_files',
				__( 'Gravity Forms Form Submissions with Files', 'gravitysmtp' ),
				$this->or_conditions( array(
					$this->and_conditions( array(
						$this->condition( 'source', '=', 'gravityforms' ),
						$this->condition( 'has_attachments', '=', 'true' ),
					) ),
				) ),
				$connector
			);
		}

		if ( class_exists( 'WooCommerce' ) ) {
			$presets[] = $this->preset_recipe(
				'woocommerce_priority_orders',
				__( 'WooCommerce Priority Orders', 'gravitysmtp' ),
				$this->or_conditions( array(
					$this->and_conditions( array(
						$this->condition( 'source', '=', 'woocommerce' ),
						$this->condition( 'subject', 'contains', 'Order' ),
						$this->condition( 'subject', 'contains', 'Completed' ),
					) ),
					$this->and_conditions( array(
						$this->condition( 'source', '=', 'woocommerce' ),
						$this->condition( 'subject', 'contains', 'Order' ),
						$this->condition( 'subject', 'contains', 'Processing' ),
					) ),
				) ),
				$connector
			);
		}

		return $presets;
	}

	private function preset_recipe( $id, $title, $conditions, $connector ) {
		return array(
			'id'          => $id,
			'title'       => $title,
			'description' => $this->preset_description( $connector, $conditions ),
			'connector'   => $connector,
			'conditions'  => $conditions,
		);
	}

	private function preset_connector() {
		$container   = Gravity_SMTP::container();
		$data_router = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
		$backup      = $data_router->get_connector_status_of_type( Connector_Status_Enum::BACKUP );

		if ( $backup && $this->is_connector_available( $backup ) ) {
			return $backup;
		}

		$primary = $data_router->get_connector_status_of_type( Connector_Status_Enum::PRIMARY );
		$data_map = $container->get( Connector_Service_Provider::CONNECTOR_DATA_MAP );

		if ( ! empty( $data_map ) && is_array( $data_map ) ) {
			foreach ( $data_map as $connector_name => $connector ) {
				if ( $primary && $connector_name === $primary ) {
					continue;
				}

				if ( $this->is_connector_available( $connector_name ) ) {
					return $connector_name;
				}
			}
		}

		return $primary ? $primary : '';
	}

	private function is_connector_available( $connector_name ) {
		$data_map = Gravity_SMTP::container()->get( Connector_Service_Provider::CONNECTOR_DATA_MAP );

		return isset( $data_map[ $connector_name ] ) && Connector_Base::is_data_map_entry_active( $data_map[ $connector_name ] );
	}

	private function preset_description( $connector, $conditions ) {
		$field_options = $this->field_options();
		$groups        = $this->get_condition_groups( $conditions );
		$segments      = array();

		foreach ( $groups as $group_index => $group ) {
			if ( $group_index > 0 ) {
				$segments[] = __( 'or', 'gravitysmtp' );
			}

			$rule_segments = array();

			foreach ( $group['rules'] as $rule ) {
				$field            = $this->get_field_option_by_value( $field_options, $rule['email_field'] );
				$field_label      = isset( $field['label'] ) ? $field['label'] : $rule['email_field'];
				$comparator_label = $this->comparator_label( $rule['comparator'] );
				$value_text       = $this->condition_value_text( $field, $rule['value'] );

				$rule_segments[] = trim( sprintf( '%s %s %s', $field_label, $comparator_label, $value_text ) );
			}

			$segments[] = implode( sprintf( ' %s ', __( 'and', 'gravitysmtp' ) ), $rule_segments );
		}

		return sprintf(
			/* translators: %1$s: connector name, %2$s: conditions. */
			__( 'Send with %1$s if %2$s', 'gravitysmtp' ),
			$this->connector_label( $connector ),
			implode( ' ', $segments )
		);
	}

	private function connector_label( $connector ) {
		if ( empty( $connector ) ) {
			return __( 'Choose Provider', 'gravitysmtp' );
		}

		$data_map = Gravity_SMTP::container()->get( Connector_Service_Provider::CONNECTOR_DATA_MAP );

		if ( ! empty( $data_map[ $connector ]['title'] ) ) {
			return $data_map[ $connector ]['title'];
		}

		return $connector;
	}

	private function get_condition_groups( $conditions ) {
		$rules = isset( $conditions['rules'] ) && is_array( $conditions['rules'] ) ? $conditions['rules'] : array();
		$groups = array();

		foreach ( $rules as $rule ) {
			if ( isset( $rule['conjunct'], $rule['rules'] ) && is_array( $rule['rules'] ) ) {
				$groups[] = $rule;
			}
		}

		if ( ! empty( $groups ) ) {
			return $groups;
		}

		if ( ! empty( $rules ) ) {
			return array(
				array(
					'conjunct' => 'and',
					'rules'    => $rules,
				),
			);
		}

		return array();
	}

	private function get_field_option_by_value( $field_options, $value ) {
		foreach ( $field_options as $field_option ) {
			if ( isset( $field_option['value'] ) && $field_option['value'] === $value ) {
				return $field_option;
			}
		}

		return array();
	}

	private function comparator_label( $comparator ) {
		foreach ( $this->comparator_options() as $option ) {
			if ( $option['value'] === $comparator ) {
				return $option['label'];
			}
		}

		return $comparator;
	}

	private function condition_value_text( $field, $value ) {
		if ( ! empty( $field['valueOptions'] ) && is_array( $field['valueOptions'] ) ) {
			foreach ( $field['valueOptions'] as $option ) {
				if ( (string) $option['value'] === (string) $value ) {
					return $option['label'];
				}
			}
		}

		return $value;
	}

	private function or_conditions( $groups ) {
		return array(
			'conjunct' => 'or',
			'rules'   => $groups,
		);
	}

	private function and_conditions( $rules ) {
		return array(
			'conjunct' => 'and',
			'rules'   => $rules,
		);
	}

	private function condition( $email_field, $comparator, $value, $regex = false ) {
		return array(
			'email_field' => $email_field,
			'comparator'  => $comparator,
			'value'       => $value,
			'regex'       => $regex,
		);
	}

	private function source_options() {
		return array(
			'theme_sources'     => $this->get_theme_sources(),
			'plugin_sources'    => $this->get_plugin_sources(),
			'mu_plugin_sources' => $this->get_mu_plugin_sources(),
			'default_sources'   => array(
				array(
					'label' => __( 'WordPress', 'gravitysmtp' ),
					'value' => 'wordpress',
				),
			),
		);
	}

	private function get_theme_sources() {
		$themes   = \wp_get_themes();
		$response = array();

		foreach ( $themes as $theme ) {
			$response[] = array(
				'label' => $theme->get( 'Name' ),
				'value' => basename( $theme->get_template_directory() ),
			);
		}

		return $response;
	}

	private function get_plugin_sources() {
		$plugins   = \get_plugins();
		$response  = array();
		$separator = defined( 'DIRECTORY_SEPARATOR' ) ? DIRECTORY_SEPARATOR : '/';

		foreach ( $plugins as $path => $plugin ) {
			$path_parts = explode( $separator, $path );
			// Single-file plugins have no directory; strip .php so values match Source_Parser slugs.
			$slug       = basename( $path_parts[0], '.php' );

			$response[] = array(
				'label' => $plugin['Name'],
				'value' => $slug,
			);
		}

		return $response;
	}

	private function get_mu_plugin_sources() {
		$plugins   = \get_mu_plugins();
		$response  = array();
		$separator = defined( 'DIRECTORY_SEPARATOR' ) ? DIRECTORY_SEPARATOR : '/';

		foreach ( $plugins as $path => $plugin ) {
			$path_parts = explode( $separator, $path );
			$slug       = basename( $path_parts[0], '.php' );

			$response[] = array(
				'label' => $plugin['Name'],
				'value' => $slug,
			);
		}

		return $response;
	}

	private function provider_options() {
		$container = Gravity_SMTP::container();

		if ( empty( $container->get( Connector_Service_Provider::CONNECTOR_DATA_MAP ) ) ) {
			return array();
		}

		$options = array();

		foreach ( $container->get( Connector_Service_Provider::CONNECTOR_DATA_MAP ) as $connector ) {
			if ( ! Connector_Base::is_data_map_entry_active( $connector ) ) {
				continue;
			}

			$options[] = array(
				'label' => isset( $connector['title'] ) ? $connector['title'] : $connector['name'],
				'value' => $connector['name'],
			);
		}

		return $options;
	}

	private function provider_labels() {
		$data_map = Gravity_SMTP::container()->get( Connector_Service_Provider::CONNECTOR_DATA_MAP );
		$labels   = array();

		if ( ! is_array( $data_map ) ) {
			return $labels;
		}

		// Inactive connectors are excluded from provider_options, but rules may still
		// reference them; ship every title so the UI can name unavailable integrations.
		foreach ( $data_map as $connector ) {
			if ( ! is_array( $connector ) || ! isset( $connector['name'] ) ) {
				continue;
			}

			$labels[ $connector['name'] ] = isset( $connector['title'] ) ? $connector['title'] : $connector['name'];
		}

		return $labels;
	}

	private function field_options() {
		$text     = $this->text_comparators();
		$number   = $this->number_comparators();
		$equality = $this->equality_comparators();

		return array(
			$this->field_option( 'subject', __( 'Subject', 'gravitysmtp' ), 'text', $text ),
			$this->field_option( 'message', __( 'Message', 'gravitysmtp' ), 'text', $text ),
			$this->field_option( 'from_email', __( 'From Email', 'gravitysmtp' ), 'text', $text ),
			$this->field_option( 'from_name', __( 'From Name', 'gravitysmtp' ), 'text', $text ),
			$this->field_option( 'to', __( 'To', 'gravitysmtp' ), 'text', $text ),
			$this->field_option( 'cc', __( 'CC', 'gravitysmtp' ), 'text', $text ),
			$this->field_option( 'bcc', __( 'BCC', 'gravitysmtp' ), 'text', $text ),
			$this->field_option( 'reply_to', __( 'Reply-To', 'gravitysmtp' ), 'text', $text ),
			$this->field_option( 'source', __( 'Source', 'gravitysmtp' ), 'source', $equality, $this->flattened_source_options() ),
			$this->field_option( 'source_subtype', __( 'Source Type', 'gravitysmtp' ), 'source_type', $equality, $this->source_type_options() ),
			$this->field_option( 'to_count', __( 'To Count', 'gravitysmtp' ), 'number', $number ),
			$this->field_option( 'cc_count', __( 'CC Count', 'gravitysmtp' ), 'number', $number ),
			$this->field_option( 'bcc_count', __( 'BCC Count', 'gravitysmtp' ), 'number', $number ),
			$this->field_option( 'has_attachments', __( 'Has Attachments', 'gravitysmtp' ), 'boolean', array( '=' ), $this->boolean_options() ),
			$this->field_option( 'attachments_count', __( 'Attachments Count', 'gravitysmtp' ), 'number', $number ),
			$this->field_option( 'content_type', __( 'Content Type', 'gravitysmtp' ), 'content_type', $equality, $this->content_type_options() ),
			$this->field_option( 'message_size', __( 'Message Size (KB)', 'gravitysmtp' ), 'number', $number ),
			$this->field_option( 'attachment_size', __( 'Attachment Size (KB)', 'gravitysmtp' ), 'number', $number ),
		);
	}

	private function field_option( $value, $label, $input_type, $comparators = array(), $value_options = array() ) {
		$option = array(
			'comparators' => $comparators,
			'inputType'   => $input_type,
			'label'       => $label,
			'value'       => $value,
		);

		if ( ! empty( $value_options ) ) {
			$option['valueOptions'] = $value_options;
		}

		return $option;
	}

	private function text_comparators() {
		return array( '=', '!=', 'contains', 'does_not_contain', 'starts_with', 'ends_with' );
	}

	private function number_comparators() {
		return array( '=', '!=', '>', '>=', '<', '<=' );
	}

	private function equality_comparators() {
		return array( '=', '!=' );
	}

	private function boolean_options() {
		return array(
			array( 'label' => __( 'True', 'gravitysmtp' ), 'value' => 'true' ),
			array( 'label' => __( 'False', 'gravitysmtp' ), 'value' => 'false' ),
		);
	}

	private function content_type_options() {
		return array(
			array( 'label' => __( 'HTML', 'gravitysmtp' ), 'value' => 'html' ),
			array( 'label' => __( 'Text', 'gravitysmtp' ), 'value' => 'text' ),
		);
	}

	private function source_type_options() {
		return array(
			array( 'label' => __( 'Core', 'gravitysmtp' ), 'value' => 'core' ),
			array( 'label' => __( 'Plugin', 'gravitysmtp' ), 'value' => 'plugin' ),
			array( 'label' => __( 'Theme', 'gravitysmtp' ), 'value' => 'theme' ),
			array( 'label' => __( 'MU Plugin', 'gravitysmtp' ), 'value' => 'mu_plugin' ),
		);
	}

	private function flattened_source_options() {
		$sources = $this->source_options();

		return array_merge(
			$sources['default_sources'],
			$sources['plugin_sources'],
			$sources['mu_plugin_sources'],
			$sources['theme_sources']
		);
	}

	private function comparator_options() {
		return array(
			array( 'label' => __( 'is', 'gravitysmtp' ), 'value' => '=' ),
			array( 'label' => __( 'is not', 'gravitysmtp' ), 'value' => '!=' ),
			array( 'label' => __( 'greater than', 'gravitysmtp' ), 'value' => '>' ),
			array( 'label' => __( 'greater than or equal to', 'gravitysmtp' ), 'value' => '>=' ),
			array( 'label' => __( 'less than', 'gravitysmtp' ), 'value' => '<' ),
			array( 'label' => __( 'less than or equal to', 'gravitysmtp' ), 'value' => '<=' ),
			array( 'label' => __( 'contains', 'gravitysmtp' ), 'value' => 'contains' ),
			array( 'label' => __( 'does not contain', 'gravitysmtp' ), 'value' => 'does_not_contain' ),
			array( 'label' => __( 'starts with', 'gravitysmtp' ), 'value' => 'starts_with' ),
			array( 'label' => __( 'ends with', 'gravitysmtp' ), 'value' => 'ends_with' ),
		);
	}

	private function normalize_recipes( $settings ) {
		$recipes = array();

		foreach ( $settings as $index => $recipe ) {
			if ( ! is_array( $recipe ) ) {
				continue;
			}

			if ( isset( $recipe['conditions'] ) && is_array( $recipe['conditions'] ) ) {
				$recipe['conditions'] = Settings_to_Conditionals_Parser::recursively_fix_stringified_bools( $recipe['conditions'] );
			}

			$recipes[] = array(
				'repeater_item_id' => sprintf( 'repeater-routing-%d', $index ),
				'name'             => isset( $recipe['name'] ) ? $recipe['name'] : sprintf( '%s %d', __( 'Rule', 'gravitysmtp' ), $index + 1 ),
				'enabled'          => isset( $recipe['enabled'] ) ? Booliesh::get( $recipe['enabled'] ) : true,
				'connector'        => isset( $recipe['connector'] ) ? $recipe['connector'] : '',
				'conditions'       => isset( $recipe['conditions'] ) && is_array( $recipe['conditions'] ) ? $recipe['conditions'] : $this->default_conditions(),
			);
		}

		return $recipes;
	}

	private function default_conditions() {
		return array(
			'conjunct' => 'or',
			'rules'   => array(),
		);
	}
}
