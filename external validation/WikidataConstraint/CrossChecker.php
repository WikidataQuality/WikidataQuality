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
        $wd_lookup = WikibaseRepo::getDefaultInstance()->getStore()->getEntityLookup();
        $wd_item_id = new ItemId( $item_id_string );
        $wd_item = $wd_lookup->getEntity($wd_item_id); // Wir vergleichen nur Items, hier ist ess aber noch eine Entity

        /*** validate item ***/
        $this->validate($wd_item, $mapping);

        return $result;
    }

    function validate(Item $wd_item, Array $mapping) {
        $wd_claims = $this->getClaims($wd_item);


        wfWaitForSlaves();
        $loadBalancer = wfGetLB();
        $db = $loadBalancer->getConnection( DB_MASTER );

        foreach (array_keys($mapping) as $wd_property_id) {
            if (in_array($wd_property_id, array_keys($wd_claims))) {

                $extEntity = $this->getExternalEntity( $db, $wd_property_id, $wd_claims[$wd_property_id]->getValue() );

                $this->crosscheck_items($wd_property_id, $wd_claims, $extEntity, $mapping);
            }
        }
    }

    function getClaims(Item $wd_item) {
        $wd_item_claims = array();
        $wd_item_statements = $wd_item->getStatements();

        foreach( $wd_item_statements as $statement ) {
            $claim = $statement->getClaim();
            $pid = $claim->getPropertyId();
            if ($claim->getMainSnak()->getType() == "value") {
                $value = $claim->getMainSnak()->getDataValue();

                $wd_item_claims[$pid->getNumericId()] = $value;
            }
        }
        return $wd_item_claims;
    }

    function crosscheck_items($wd_identifier_pid, Array $wd_item_claims, $ext_item, Array $mapping) {
        /*global $mapping;*/
        global $result;

        $wd_validatable_properties = array_keys($mapping[$wd_identifier_pid]); //Example: Array containing 569
        $wd_validatable_claims = array();

        foreach ($wd_validatable_properties as $wd_property_id) {
            if (in_array($wd_property_id, array_keys($wd_item_claims))) {
                $wd_validatable_claims[$wd_property_id] = $wd_item_claims[$wd_property_id];
            }
        }


        foreach (array_keys($wd_validatable_claims) as $wd_property_id){
            $reflect = new ReflectionClass($wd_validatable_claims[$wd_property_id]);
            switch( $reflect->getShortName() ) {
                case "EntityIdValue":
                    $entityId = $wd_validatable_claims[$wd_property_id]->getEntityId();
                    $terms = $this->getTerms( $entityId );
                    $extValues = $this->evaluateMapping( $ext_item, $mapping[$wd_identifier_pid][$wd_property_id] );
                    if ( count( array_intersect($terms, $extValues) ) > 0 ) {
                        print "true";
                    }
                    break;

                case "StringValue":
                    break;
            }
        }
    }

    function getTerms( $itemId ){
        $lookup = WikibaseRepo::getDefaultInstance()->getStore()->getEntityLookup();    //TODO make global
        $entity = $lookup->getEntity($itemId);
        $aliases = $entity->getAliases("de");
        $label = array($entity->getLabel("de"));                                    //cast to array for merging in next line
        $terms = array_merge($aliases, $label);

        return $terms;
    }

    function getExternalEntity( $db, $pid, $extId ) {
        $result = $db->select( "wdq_external_data", "external_data", array( "pid=$pid", "external_id=$extId" ), __METHOD__ );
        foreach ( $result as $row ) {
            return $row->entity_data;
        }
    }

    function evaluateMapping( $extEntity, $mapping ) {
        $doc = new DomDocument();
        $doc->loadXML( $extEntity );
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