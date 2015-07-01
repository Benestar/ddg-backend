<?php

namespace DDGWikidata;

use DataValues\DataValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;

/**
 * Formatter for a list of data values.
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class DataValuesFormatter {

	/**
	 * @var ApiInteractor
	 */
	private $apiInteractor;

	public function __construct( ApiInteractor $apiInteractor ) {
		$this->apiInteractor = $apiInteractor;
	}

	/**
	 * @param StatementList $statements
	 * @param PropertyId $propertyId
	 * @return DataValue[]
	 */
	public function getBestValues( StatementList $statements, PropertyId $propertyId ) {
		$values = array();

		$bestStatements = $statements->getByPropertyId( $propertyId )->getBestStatements();
		foreach ( $bestStatements->toArray() as $statement ) {
			if ( $statement->getMainSnak() instanceof PropertyValueSnak ) {
				$values[] = $statement->getMainSnak()->getDataValue();
			}
		}

		return $values;
	}

	/**
	 * @param DataValue[] $values
	 * @param string $lang
	 * @return array
	 */
	public function formatDataValues( array $values, $lang ) {
		$formatted = array();

		$items = $this->apiInteractor->getItems( $this->getItemIds( $values ) );

		foreach ( $values as $value ) {
			if ( $value instanceof EntityIdValue ) {
				$formatted[] = $this->formatItem( $items[$value->getEntityId()->getSerialization()], $lang );
			} else {
				$formatted[] = $this->formatDataValue( $value, $lang );
			}
		}

		return $formatted;
	}

	/**
	 * @param DataValue[] $values
	 * @return string[]
	 */
	private function getItemIds( array $values ) {
		$ids = array();

		foreach ( $values as $value ) {
			if ( $value instanceof EntityIdValue ) {
				$ids[] = $value->getEntityId()->getSerialization();
			}
		}

		return $ids;
	}

	/**
	 * @param Item $item
	 * @param string $lang
	 * @return array
	 */
	private function formatItem( Item $item, $lang ) {
		return array(
			'title' => $item->getLabel( $lang ),
			'subtitle' => $item->getDescription( $lang ),
			'image' => $this->getImage( $item )
		);
	}

	/**
	 * @param Item $item
	 * @return string|bool
	 */
	private function getImage( Item $item ) {
		$values = $this->getBestValues( $item->getStatements(), new PropertyId( 'P18' ) );

		if ( empty( $values ) ) {
			return false;
		}

		return $this->apiInteractor->getImageUrl( $values[0]->getValue(), '85px' );
	}

	/**
	 * @param DataValue $value
	 * @param string $lang
	 * @return array
	 */
	private function formatDataValue( DataValue $value, $lang ) {
		return array(
			'title' => $value->getSortKey()
		);
	}

}