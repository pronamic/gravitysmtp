<?php

namespace Gravity_Forms\Gravity_SMTP\Routing\Handlers;

interface Routing_Handler {

	/**
	 * Handle the routing callback from gravitysmtp_connector_for_sending
	 *
	 * @since 1.2
	 * @since 2.2.1 Added the `$source` parameter.
	 *
	 * @param string|bool $current_connector The connector currently chosen for sending, or `false` when none has been selected yet.
	 * @param array       $email_data        The data for the current email.
	 *
	 * @return string|bool The connector type to use for sending, or `false` to abort the send.
	 */
	public function handle( $current_connector, $email_data );

}
