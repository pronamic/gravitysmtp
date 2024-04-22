<?php

if ( ! function_exists( 'get_option' ) ) {
	return;
}

$is_configured = \Gravity_Forms\Gravity_SMTP\Handler\Mail_Handler::is_minimally_configured();

if( ! function_exists( 'wp_mail' ) && $is_configured ) {
	function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		return \Gravity_Forms\Gravity_SMTP\Gravity_SMTP::container()->get( \Gravity_Forms\Gravity_SMTP\Handler\Handler_Service_Provider::HANDLER )->mail( $to, $subject, $message, $headers, $attachments );
	}
}