<?php

namespace Wikibase\Repo;


use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevisionLookup;

class WikidataApiEntityRevisionLookup extends WikidataApiLookup implements EntityRevisionLookup {

    /**
     * @param EntityId $entityId
     * @param int|string $revisionId
     * @throws \Exception
     */
    public function getEntityRevision( EntityId $entityId, $revisionId = self::LATEST_FROM_SLAVE ) {
        throw new \Exception( 'Not supported for API lookup.' );
    }

    /**
     * @param EntityId $entityId
     * @param string $mode
     * @return int|false
     */
    public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_SLAVE ) {
        $parameter = array(
            'ids' => $entityId->getSerialization(),
            'props' => 'info'
        );
        $responseBody = $this->sendRequest( $parameter );
        $responseBodyJson = json_decode( $responseBody, true );
        $revisionId = $responseBodyJson[ 'entities' ][ (string)$entityId ][ 'lastrevid' ];

        return $revisionId;
    }
}