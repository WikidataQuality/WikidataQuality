<?php

namespace Wikibase\Repo;


use Wikibase\Lib\Store\EntityLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use DataValues\Deserializers\DataValueDeserializer;


/**
 * Class WikidataApiEntityLookup
 * @package Wikibase
 * @author BP2014N1
 * @licence GNU GPL v2+
 */
class WikidataApiEntityLookup implements EntityLookup
{
    /**
     * Wikidata API endpoint to get entities.
     */
    const API_ENDPOINT = "https://www.wikidata.org/w/api.php?action=wbgetentities&ids=%s&format=json";


    /**
     * Returns the entity with the provided id or null if there is no such entity.
     * @param EntityId $entityId
     */
    public function getEntity( EntityId $entityId )
    {
        // Send request
        $responseBody = $this->sendRequest( $entityId );

        // Parse response
        $entityJson = $this->parseApiResponse( $entityId, $responseBody );
        if ( $entityJson ) {
            $deserializerFactory = new DeserializerFactory(
                new DataValueDeserializer(
                    array(
                        'boolean' => 'DataValues\BooleanValue',
                        'number' => 'DataValues\NumberValue',
                        'string' => 'DataValues\StringValue',
                        'unknown' => 'DataValues\UnknownValue',
                        'globecoordinate' => 'DataValues\GlobeCoordinateValue',
                        'monolingualtext' => 'DataValues\MonolingualTextValue',
                        'multilingualtext' => 'DataValues\MultilingualTextValue',
                        'quantity' => 'DataValues\QuantityValue',
                        'time' => 'DataValues\TimeValue',
                        'wikibase-entityid' => 'Wikibase\DataModel\Entity\EntityIdValue',
                    )
                ),
                new BasicEntityIdParser()
            );

            return $deserializerFactory->newEntityDeserializer()->deserialize( $entityJson );
        }
    }

    /**
     * Returns whether the given entity can bee looked up using getEntity().
     * @param EntityId $entityId
     */
    public function hasEntity( EntityId $entityId )
    {
        // Send request
        $responseBody = $this->sendRequest( $entityId );

        // Parse response
        if ( $this->parseApiResponse( $entityId, $responseBody ) ) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Sends API request to get a single entity from Wikidata API and returns response as json.
     * @param \EntityId $entityId
     * @return string - response body parsed as json.
     */
    private function sendRequest( $entityId )
    {
        // Build url
        $url = sprintf( self::API_ENDPOINT, (string)$entityId );

        // Send request
        $arrContextOptions=array (
            "ssl"=>array (
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        ); 
        $responseBody = file_get_contents( $url, false, stream_context_create( $arrContextOptions ) );

        return $responseBody;
    }

    /**
     * Parses an API response and returns json representation of an entity
     * @param \EntityId $entityId
     * @param string $responseBody
     * @return array
     */
    private function parseApiResponse( $entityId, $responseBody )
    {
        // Parse as json
        $responseBodyJson = json_decode( $responseBody, true );

        // Extract single entity
        $entityJson = $responseBodyJson[ 'entities' ][ (string)$entityId ];

        // Check, if entity exists
        if ( !isset( $entityJson[ 'missing' ] ) ) {
            return $entityJson;
        }
    }
}