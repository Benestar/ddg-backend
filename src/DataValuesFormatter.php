<?php

namespace DDGWikidata;

use DataValues\DataValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;

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
			'subtitle' => $item->getDescription( $lang )
		);
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
