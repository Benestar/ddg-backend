<?php

namespace DDGWikidata;

use DataValues\DataValue;
use Mediawiki\Api\MediawikiApi;
use Wikibase\Api\Service\RevisionsGetter;
use Wikibase\Api\WikibaseFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
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
	 * @var RevisionsGetter
	 */
	private $revisionsGetter;

	/**
	 * @param string $wikibaseApi
	 */
	public function __construct( $wikibaseApi ) {
		$this->api = new MediawikiApi( $wikibaseApi );

		$wikibaseFactory = new WikibaseFactory( $this->api );
		$this->revisionsGetter = $wikibaseFactory->newRevisionsGetter();
	}

	/**
	 * @param string $subject
	 * @param string $property
	 * @param string $lang
	 * @return array
	 */
	public function getResult( $subject, $property, $lang ) {
		$itemIds = $this->searchEntities( $subject, 'item', $lang );
		$propertyIds = $this->searchEntities( $property, 'property', $lang );

		$items = $this->getItems( $itemIds );
		list( $itemId, $propertyId, $values ) = $this->getDataValues( $items, $propertyIds );

		return array(
			'item' => $itemId,
			'property' => $propertyId,
			'values' => $this->formatDataValues( $values )
		);
	}

	/**
	 * @param string $search
	 * @param string $type
	 * @param string $lang
	 * @return EntityId[]
	 */
	private function searchEntities( $search, $type, $lang ) {
		$response = $this->api->getAction( 'wbsearchentities', array(
			'search' => $search,
			'type' => $type,
			'language' => $lang
		) );

		$ids = array();

		foreach ( $response['search'] as $search ) {
			switch ( $type ) {
				case 'item':
					$ids[] = new ItemId( $search['id'] );
					break;
				case 'property':
					$ids[] = new PropertyId( $search['id'] );
					break;
			}
		}

		return $ids;
	}

	/**
	 * @param string[] $itemIds
	 * @return Item[]
	 */
	private function getItems( array $itemIds ) {
		$revisions = $this->revisionsGetter->getRevisions( $itemIds );
		$items = array();

		foreach ( $revisions->toArray() as $revision ) {
			$items[] = $revision->getContent()->getNativeData();
		}

		return $items;
	}

	/**
	 * @param Item[] $items
	 * @param PropertyId[] $propertyIds
	 * @return DataValue[]
	 */
	private function getDataValues( array $items, array $propertyIds ) {
		foreach ( $items as $item ) {
			foreach ( $propertyIds as $propertyId ) {
				$bestValues = $this->getBestValues( $item->getStatements(), $propertyId );
				if ( !empty( $bestValues ) ) {
					return array( $item->getId()->getSerialization(), $propertyId->getSerialization(), $bestValues );
				}
			}
		}

		return array();
	}

	
	/**
	 * @param StatementList $statements
	 * @param PropertyId $propertyId
	 * @return DataValue[]
	 */
	private function getBestValues( StatementList $statements, PropertyId $propertyId ) {
		$values = array();

		$bestStatements = $statements->getWithPropertyId( $propertyId )->getBestStatements();
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
			$formatted[] = $value->toArray();
		}

		return $formatted;
	}

}
