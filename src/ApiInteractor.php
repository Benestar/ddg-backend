<?php

namespace DDGWikidata;

use DataValues\DataValue;
use DataValues\Serializers\DataValueSerializer;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\SimpleRequest;
use Wikibase\Api\Service\RevisionsGetter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;

/**
 * Description of ApiInteractor
 *
 * @author Benedikt
 */
class ApiInteractor {

	/**
	 * @var MediawikiApi
	 */
	private $api;

	/**
	 * @var RevisionsGetter
	 */
	private $revisionsGetter;

	public function __construct( MediawikiApi $api, RevisionsGetter $revisionsGetter ) {
		$this->api = $api;
		$this->revisionsGetter = $revisionsGetter;
	}

	/**
	 * @param string $search
	 * @param string $type
	 * @param string $lang
	 * @return EntityId[]
	 */
	public function searchEntities( $search, $type, $lang ) {
		$response = $this->api->getRequest( new SimpleRequest( 'wbsearchentities', array(
			'search' => $search,
			'type' => $type,
			'language' => $lang
		) ) );

		$ids = array();

		foreach ( $response['search'] as $search ) {
			$ids[] = $search['id'];
		}

		return $ids;
	}

	/**
	 * @param string[] $itemIds
	 * @return Item[]
	 */
	public function getItems( array $itemIds ) {
		$revisions = $this->revisionsGetter->getRevisions( $itemIds );
		$items = array();

		foreach ( $revisions->toArray() as $revision ) {
			$item = $revision->getContent()->getData();
			$items[$item->getId()->getSerialization()] = $item;
		}

		return $items;
	}

	/**
	 * @param string $fileName
	 * @param int $height
	 * @return string
	 */
	public function getImageUrl( $fileName, $height ) {
		$response = $this->api->getRequest( new SimpleRequest( 'query', array(
			'prop' => 'imageinfo',
			'iiprop' => 'url',
			'iiurlheight' => $height,
			'titles' => 'File:' . $fileName
		) ) );

		return $response['query']['pages']['-1']['imageinfo'][0]['thumburl'];
	}

	/**
	 * @param DataValue $dataValue
	 * @return string
	 */
	public function formatDataValue( DataValue $dataValue ) {
		$serializer = new DataValueSerializer();
		$response = $this->api->getRequest( new SimpleRequest( 'wbformatvalue', array(
			'generate' => 'text/html',
			'datavalue' => json_encode( $serializer->serialize( $dataValue ) )
		) ) );

		return $response['result'];
	}

}
