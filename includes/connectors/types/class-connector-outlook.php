<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;

/**
 * Connector for 365 / Outlook
 *
 * @since 1.0
 */
class Connector_Outlook extends Connector_Base {

	protected $name        = '365-outlook';
	protected $title       = '365 / Outlook';
	protected $disabled    = true;
	protected $description = '365 / Outlook is a popular and robust payment processing platform that allows businesses and websites to accept credit card payments online.';
	protected $logo        = 'Microsoft';
	protected $full_logo   = '';

	/**
	 * Sending logic.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function send() {
		// @todo - set up actual send logic.
		return true;
	}

	/**
	 * Connector data.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function connector_data() {
		// @todo - set up actual connector data.
		return array();
	}

	/**
	 * Settings fields.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function settings_fields() {
		// @todo - set up actual settings fields.
		return array();
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
		return array(
			self::SETTING_ACTIVATED  => true,
			self::SETTING_CONFIGURED => false,
			self::SETTING_ENABLED    => false,
			'disabled' => $this->disabled,
		);
	}

}
