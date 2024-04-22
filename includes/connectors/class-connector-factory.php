<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors;

class Connector_Factory {

	protected $php_mailer;
	protected $data_store;
	protected $logger;
	protected $emails;
	protected $header_parser;
	protected $recipient_parser;

	public function __construct( $php_mailer, $data_store, $logger, $emails, $header_parser, $recipient_parser ) {
		$this->php_mailer       = $php_mailer;
		$this->data_store       = $data_store;
		$this->logger           = $logger;
		$this->emails           = $emails;
		$this->header_parser    = $header_parser;
		$this->recipient_parser = $recipient_parser;
	}

	public function create( $type ) {
		$classname = sprintf( '%s\Types\Connector_%s', __NAMESPACE__, ucfirst( $type ) );

		if ( ! class_exists( $classname ) ) {
			throw new \InvalidArgumentException( 'Connector type for type ' . $type . ' with class ' . $classname . ' does not exist.' );
		}

		return new $classname( $this->php_mailer, $this->data_store, $this->logger, $this->emails, $this->header_parser, $this->recipient_parser );
	}

}