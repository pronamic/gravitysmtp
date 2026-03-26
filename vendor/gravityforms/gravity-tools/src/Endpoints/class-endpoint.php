<?php

namespace Gravity_Forms\Gravity_Tools\Endpoints;

abstract class Endpoint {

	protected $required_params = array();

	protected $minimum_cap = 'manage_options';

	abstract public function handle();

	/**
	 * Default nonce.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	protected function get_nonce_name() {
		return -1;
	}

	/**
	 * Default validation, checks ajax referer and verifies required params.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	protected function validate() {
		check_ajax_referer( $this->get_nonce_name(), 'security' );

		if ( ! current_user_can( $this->minimum_cap ) ) {
			return false;
		}

		foreach( $this->required_params as $param ) {
			if ( ! isset( $_REQUEST[ $param ] ) ) {
				return false;
			}
		}

		return true;
	}

}
