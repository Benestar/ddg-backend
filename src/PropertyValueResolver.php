<?php

namespace DDGWikidata;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Mediawiki\Api\MediawikiApi;
use RuntimeException;
use Wikibase\Api\WikibaseFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Finds results for the given property and value
 * based on data from Wikidata.
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyValueResolver {

	/**
	 * @var string[]
	 */
	private static $dataValueClasses = array(
		'unknown' => 'DataValues\UnknownValue',
		'string' => 'DataValues\StringValue',
		'boolean' => 'DataValues\BooleanValue',
		'number' => 'DataValues\NumberValue',
		'globecoordinate' => 'DataValues\Geo\Values\GlobeCoordinateValue',
		'monolingualtext' => 'DataValues\MonolingualTextValue',
		'multilingualtext' => 'DataValues\MultilingualTextValue',
		'quantity' => 'DataValues\QuantityValue',
		'time' => 'DataValues\TimeValue',
		'wikibase-entityid' => 'Wikibase\DataModel\Entity\EntityIdValue',
	);

	/**
	 * @var ApiInteractor
	 */
	private $apiInteractor;

	/**
	 * @var DataValuesFormatter
	 */
	private $dataValuesFormatter;

	/**
	 * @param string $apiUrl
	 */
	public function __construct( $apiUrl ) {
		$api = new MediawikiApi( $apiUrl );
		$wikibaseFactory = new WikibaseFactory(
			$api,
			new DataValueDeserializer( self::$dataValueClasses ),
			new DataValueSerializer()
		);
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
				$bestValues = $this->dataValuesFormatter->getBestValues( $item->getStatements(), new PropertyId( $propertyId ) );
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

}
