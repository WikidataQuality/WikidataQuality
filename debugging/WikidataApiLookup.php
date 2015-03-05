<?php

namespace Wikibase\Repo;


use DataValues\Deserializers\DataValueDeserializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;


/**
 * Class WikidataApiLookup
 * @package Wikibase\Repo
 * @author BP2014N1
 * @licence GNU GPL v2+
 */
abstract class WikidataApiLookup
{
    /**
     * Wikidata API endpoint to get labels.
     */
    const API_ENDPOINT = "https://www.wikidata.org/w/api.php?action=wbgetentities&format=json";

    protected $entityDeserializer;


    public function __construct()
    {
        $this->entityDeserializer = WikibaseRepo::getDefaultInstance()->getInternalEntityDeserializer();
    }


    protected function requestEntity( $entityId, $parameter = array() )
    {
        // Add entity id to parameters
        $parameter[ 'ids' ] = (string)$entityId;

        // Add property datatype
        if ( array_key_exists( 'props', $parameter) ) {
            $parameter[ 'props' ] .= '|datatype';
        }

        // Send request
        $responseBody = $this->sendRequest( $parameter );

        // Parse response
        $entityJson = $this->parseApiResponse( $entityId, $responseBody );
        if ( $entityJson ) {
            return $this->entityDeserializer->deserialize( $entityJson );
        }
    }

    /**
     * Sends API request to get a single entity from Wikidata API and returns response as json.
     * @param array $parameter
     * @return string - response body parsed as json.
     */
    protected function sendRequest( $parameter )
    {
        // Build url
        $url = self::API_ENDPOINT;
        foreach ( $parameter as $key => $value ) {
            $url .= "&$key=$value";
        }

        // Send request
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
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
    protected function parseApiResponse( $entityId, $responseBody )
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