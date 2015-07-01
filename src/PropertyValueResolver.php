<?php

namespace DDGWikidata;

use DataValues\DataValue;
use Mediawiki\Api\MediawikiApi;
use RuntimeException;
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
	 * @var ApiInteractor
	 */
	private $apiInteractor;

	/**
	 * @var DataValuesFormatter
	 */
	private $dataValuesFormatter;

	/**
	 * @param string $wikibaseApi
	 */
	public function __construct( $wikibaseApi ) {
		$api = new MediawikiApi( $wikibaseApi );
		$wikibaseFactory = new WikibaseFactory( $api );
		$revisionsGetter = $wikibaseFactory->newRevisionsGetter();

		$this->apiInteractor = new ApiInteractor( $api, $revisionsGetter );
		$this->dataValuesFormatter = new DataValuesFormatter( $this->apiInteractor );
	}

	/**
	 * @param string $subject
	 * @param string $property
	 * @param string $lang
	 * @return array
	 */
	public function resolvePropertyValue( $subject, $property, $lang ) {
		$itemIds = $this->apiInteractor->searchEntities( $subject, 'item', $lang );
		$propertyIds = $this->apiInteractor->searchEntities( $property, 'property', $lang );

		$items = $this->apiInteractor->getItems( $itemIds );
		return $this->getResult( $items, $propertyIds, $lang );
	}

	/**
	 * @param Item[] $items
	 * @param string[] $propertyIds
	 * @param string $lang
	 * @return array
	 * @throws RuntimeException
	 */
	private function getResult( array $items, array $propertyIds, $lang ) {
		foreach ( $items as $item ) {
			foreach ( $propertyIds as $propertyId ) {
				$bestValues = $this->getBestValues( $item->getStatements(), new PropertyId( $propertyId ) );
				if ( !empty( $bestValues ) ) {
					return array(
						'item' => $item->getId()->getSerialization(),
						'property' => $propertyId,
						'values' => $this->dataValuesFormatter->formatDataValues( $bestValues, $lang )
					);
				}
			}
		}

		throw new RuntimeException( 'Didn\'t find any matching values.' );
	}

	
	/**
	 * @param StatementList $statements
	 * @param PropertyId $propertyId
	 * @return DataValue[]
	 */
	private function getBestValues( StatementList $statements, PropertyId $propertyId ) {
		$values = array();

		$bestStatements = $statements->getByPropertyId( $propertyId )->getBestStatements();
		foreach ( $bestStatements->toArray() as $statement ) {
			if ( $statement->getMainSnak() instanceof PropertyValueSnak ) {
				$values[] = $statement->getMainSnak()->getDataValue();
			}
		}

		return $values;
	}

}
