<?php

namespace DDGWikidata;

use DataValues\DataValue;
use Mediawiki\Api\MediawikiApi;
use Wikibase\Api\Service\RevisionGetter;
use Wikibase\Api\WikibaseFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;

/**
 * Finds results for the given property and value
 * based on data from Wikidata.
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyValueResolver {

	/**
	 * @var MediawikiApi
	 */
	private $api;

	/**
	 * @var RevisionGetter
	 */
	private $revisionGetter;

	/**
	 * @param string $wikibaseApi
	 */
	public function __construct( $wikibaseApi ) {
		$this->api = new MediawikiApi( $wikibaseApi );

		$wikibaseFactory = new WikibaseFactory( $this->api );
		$this->revisionGetter = $wikibaseFactory->newRevisionGetter();
	}

	/**
	 * @param string $subject
	 * @param string $property
	 * @param string $lang
	 * @return array
	 */
	public function getResult( $subject, $property, $lang ) {
		$itemId = $this->searchEntity( $subject, 'item', $lang );
		$propertyId = $this->searchEntity( $property, 'property', $lang );

		if ( $itemId === null || $property === null ) {
			return array();
		}

		$item = $this->getItem( $itemId );
		$values = $this->getDataValues( $item->getStatements(), new PropertyId( $propertyId ) );

		return $this->formatDataValues( $values );
	}

	/**
	 * @param string $search
	 * @param string $type
	 * @param string $lang
	 * @return string|null
	 */
	private function searchEntity( $search, $type, $lang ) {
		$response = $this->api->getAction( 'wbsearchentities', array(
			'search' => $search,
			'type' => $type,
			'language' => $lang
		) );

		if ( empty( $response['search'] ) ) {
			return null;
		}

		return $response['search'][0]['id'];
	}

	/**
	 * @param string $itemId
	 * @return Item
	 */
	private function getItem( $itemId ) {
		return $this->revisionGetter->getFromId( $itemId )->getContent()->getNativeData();
	}

	/**
	 * @param StatementList $statements
	 * @param PropertyId $propertyId
	 * @return DataValue[]
	 */
	private function getDataValues( StatementList $statements, PropertyId $propertyId ) {
		$bestStatements = $statements->getWithPropertyId( $propertyId )->getBestStatements();
		$values = array();

		foreach ( $bestStatements->toArray() as $statement ) {
			if ( $statement->getMainSnak() instanceof PropertyValueSnak ) {
				$values[] = $statement->getMainSnak()->getDataValue();
			}
		}

		return $values;
	}

	/**
	 * @param DataValue[] $values
	 * @return array
	 */
	private function formatDataValues( array $values ) {
		$formatted = array();

		foreach ( $values as $value ) {
			$formatted[] = $value->getValue();
		}

		return $formatted;
	}

}
