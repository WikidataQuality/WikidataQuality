<?php

namespace Wikibase\Repo;


use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;

class WikidataApiEntityRevisionLookup extends WikidataApiLookup implements EntityRevisionLookup {

    /**
     * @param EntityId $entityId
     * @param int|string $revisionId
     * @returns EntityRevision|null
     */
    public function getEntityRevision( EntityId $entityId, $revisionId = self::LATEST_FROM_SLAVE ) {
        $parameter = array(
            'ids' => $entityId->getSerialization()
        );
        $responseBody = $this->sendRequest( $parameter );
        $entityJson = $this->parseApiResponse( $entityId, $responseBody );
        if( $entityJson ) {
            $entity = $this->entityDeserializer->deserialize( $entityJson );

            return new EntityRevision( $entity, $entityJson[ 'lastrevid' ] );
        }
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
        $entityJson = $this->parseApiResponse( $entityId, $responseBody );

        if( $entityJson ) {
            return $entityJson[ 'lastrevid' ];
        }
        else {
            return false;
        }
    }
}