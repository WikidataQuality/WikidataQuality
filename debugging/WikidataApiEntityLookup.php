<?php

namespace Wikibase\Repo;


use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityLookup;


/**
 * Class WikidataApiEntityLookup
 * @package Wikibase\Repo
 * @author BP2014N1
 * @licence GNU GPL v2+
 */
class WikidataApiEntityLookup extends WikidataApiLookup implements EntityLookup
{
    /**
     * Returns the entity with the provided id or null if there is no such entity.
     * @param EntityId $entityId
     */
    public function getEntity( EntityId $entityId )
    {
        return $this->requestEntity( $entityId );
    }

    /**
     * Returns whether the given entity can bee looked up using getEntity().
     * @param EntityId $entityId
     */
    public function hasEntity( EntityId $entityId )
    {
        // Send request
        $responseBody = $this->requestEntity( $entityId );

        // Parse response
        if ( $this->parseApiResponse( $entityId, $responseBody ) ) {
            return true;
        } else {
            return false;
        }
    }
}