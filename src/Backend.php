<?php

namespace DDGWikidata;

use Exception;

/**
 * Entry point of the Wikidata DuckDuckGo backend application.
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class Backend {

	/**
	 * @var string[]
	 */
	private $params;

	/**
	 * @var string
	 */
	private $wikibaseApi;

	public function __construct( array $params, $wikibaseApi ) {
		$this->params = $params;
		$this->wikibaseApi = $wikibaseApi;
	}

	public function execute() {
		try {
			$this->checkParams();
			$this->outputResult( array( 'result' => $this->getResult() ) );
		}
		catch ( Exception $ex ) {
			@http_response_code( 400 );
			$this->outputResult( array( 'error' => $ex->getMessage() ) );
		}
	}

	private function checkParams() {
		if ( empty( $this->params ) ) {
			throw new Exception( 'Please provide a `subject` and a `property`. The optional `lang` parameter defaults to "en".' );
		}

		if ( !isset( $this->params['subject'] ) || !isset( $this->params['property'] ) ) {
			throw new Exception( 'The `subject` and `property` parameters are required.' );
		}
	}

	private function getResult() {
		$propertyValueResolver = new PropertyValueResolver( $this->wikibaseApi );

		return $propertyValueResolver->getResult(
			$this->params['subject'],
			$this->params['property'],
			$this->params['lang'] ?: 'en'
		);
	}

	private function outputResult( array $result ) {
		@header( 'Content-type: application/json' );
		echo json_encode( $result );
	}

}
