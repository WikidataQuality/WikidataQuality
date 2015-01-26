<?php

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Repo\Store;
use Wikibase\DataModel\Snak;

/*$mapping = array(
    "434" => array(
        "mb_url" => "http://musicbrainz.org/ws/2/artist/%s?inc=artist-rels&fmt=json",
        "property_mapping" => array(
            "569" => "$.\"life-span\".begin" // date of birth
        )
    )
);*/

$result = array();

class CrossChecker {

    function crosscheckItem($item_id_string){
        global $result;

        $mapping = array(
            "227" => array(
                "26" => array(
                    "nodeSelector" => '/record/datafield[@tag="500" and subfield[@code="9"]="v:Ehemann" or subfield[@code="9"]="v:Ehefrau"]/subfield[@code="a"]/text()',
                    "valueFormatter" => 'concat(substring-after(., ", "), " ", substring-before(., ", "))'
                )
            )
        );

        /*** get lookup & lookup entity ***/
        $wd_lookup = WikibaseRepo::getDefaultInstance()->getStore()->getEntityLookup();     //TODO make global
        $wd_item_id = new ItemId( $item_id_string );
        $wd_item = $wd_lookup->getEntity($wd_item_id);

        /*** validate item ***/
        $this->validate($wd_item, $mapping);

        /*** result is an array of strings ***/
        return $result;
    }

    function validate( Item $wd_item, Array $mapping ) {
        /*** get claims belonging to this wd_item ***/
        $wd_item_claims = $this->getClaims( $wd_item );

        /*** get db connection ***/
        wfWaitForSlaves();
        $loadBalancer = wfGetLB();
        $db = $loadBalancer->getConnection( DB_MASTER );

        /*** get external entity for wd_item which has an item for crosscheck and crosscheck both items ***/
        foreach ( array_keys( $mapping ) as $wd_identifier_pid ) {
            if ( in_array( $wd_identifier_pid, array_keys( $wd_item_claims ) ) ) {
                $external_entity = $this->getExternalEntity( $db, $wd_identifier_pid, $wd_item_claims[$wd_identifier_pid]->getValue() );

                $this->crosscheck_items( $wd_identifier_pid, $wd_item_claims, $external_entity, $mapping );
            }
        }
    }

    function getClaims(Item $wd_item) {
        /*** get the claims for every statement of the item ***/
        $wd_item_claims = array();
        $wd_item_statements = $wd_item->getStatements();

        foreach( $wd_item_statements as $statement ) {
            $claim = $statement->getClaim();
            $claim_pid = $claim->getPropertyId();
            $mainSnak = $claim->getMainSnak();

            /*** get data value only if it's a PropertyValueSnak
             * and not a PropertySomeValueSnak or PropertyNoValueSnak ***/
            if ($mainSnak->getType() == "value") {
                $value = $mainSnak->getDataValue();
                $wd_item_claims[$claim_pid->getNumericId()] = $value;
            }
        }
        return $wd_item_claims;
    }

    function crosscheck_items( $wd_identifier_pid, Array $wd_item_claims, $external_item, Array $mapping ) {
        /*global $mapping;*/
        global $result;

        $wd_validatable_properties = array_keys( $mapping[$wd_identifier_pid] );
        $wd_validatable_claims = array();

        /*** get validatable claims of wd_item ***/
        foreach ( $wd_validatable_properties as $wd_validatable_pid ) {
            if ( in_array( $wd_validatable_pid, array_keys( $wd_item_claims ) ) ) {
                $wd_validatable_claims[$wd_validatable_pid] = $wd_item_claims[$wd_validatable_pid];
            }
        }

        /*** check items and print out result ***/
        foreach ( array_keys($wd_validatable_claims) as $wd_validatable_pid ){
            $reflect = new ReflectionClass( $wd_validatable_claims[$wd_validatable_pid] );

            switch( $reflect->getShortName() ) {
                /*** get terms (label + aliases) of related wd_item if claim contains EntityIdValue and get result ***/
                case "EntityIdValue":
                    $wd_entity_id = $wd_validatable_claims[$wd_validatable_pid]->getEntityId();
                    $wd_entity_terms = $this->getTerms( $wd_entity_id );
                    $external_values = $this->evaluateMapping( $external_item, $mapping[$wd_identifier_pid][$wd_validatable_pid] );
                    if ( count( array_intersect( $wd_entity_terms, $external_values ) ) > 0 ) {
                        print "true";
                    }
                    break;

                /*** directly get the result if claim contains StringValue ***/
                case "StringValue":
                    break;
            }
        }
    }

    function getTerms( $wd_entity_id ){
        /*** get terms (label + aliases) of related wd_item ***/
        $lookup = WikibaseRepo::getDefaultInstance()->getStore()->getEntityLookup();                //TODO make global
        $wd_entity = $lookup->getEntity($wd_entity_id);
        $wd_entity_aliases = $wd_entity->getAliases("de");                                          //TODO get language from db
        $wd_entity_label = array($wd_entity->getLabel("de")); // cast to array for merging in next line
        $wd_entity_terms = array_merge($wd_entity_aliases, $wd_entity_label);
        return $wd_entity_terms;
    }

    function getExternalEntity( $db, $pid, $external_entity_id ) {
        /*** get entity of db beloning to $pid and $external_entity_id ***/
        $result = $db->select( "wdq_external_data", "external_data", array( "pid=$pid", "external_id=$external_entity_id" ), __METHOD__ );
        foreach ( $result as $row ) {
            return $row->entity_data; // we only gets 1 result!
        }
    }

    function evaluateMapping( $external_entity, $mapping ) {
        $doc = new DomDocument();
        $doc->loadXML( $external_entity );
        $domXpath = new DOMXPath( $doc );
        $result = $domXpath->evaluate( $mapping["nodeSelector"] );

        $values = array();
        foreach ( $result as $entry ) {
            if ( $entry instanceof DOMNode && !empty($mapping["valueFormatter"]) ) {
                $values[] = $domXpath->evaluate( $mapping["valueFormatter"], $entry );
            }
            else {
                $values[] = $entry->textContent;
            }
        }

        return $values;
    }
}