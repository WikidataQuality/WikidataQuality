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
            "434" => array(
                "mb_url" => "http://musicbrainz.org/ws/2/artist/%s?inc=artist-rels&fmt=json",
                "property_mapping" => array(
                    "569" => "$.\"life-span\".begin" // date of birth
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
        /*global $mapping;*/

        //$wd_claims = $this->getClaims($wd_item); - könnte so aussehen:
        $wd_item_claims = array(
            "434" => array("", "string", "456eabce-d1dd-4481-a206-36ab4f2eaeb8"),
            "19" => array("24826", "string", "Liverpool")
        );

        foreach (array_keys($mapping) as $wd_property_id) {
            if (in_array($wd_property_id, array_keys($wd_item_claims))) {

                /* get mapped MusicBrainz item */
                $mb_identifier = $wd_item_claims[$wd_property_id][2];
                $mb_url = sprintf($mapping[$wd_property_id]["mb_url"], $mb_identifier);
                $mb_item_json = $this->get_json_response($mb_url);

                $this->crosscheck_items($wd_property_id, $wd_item_claims, $mb_item_json, $mapping);
            }
        }
    }

    function getClaims(Item $wd_item) {
        $wd_item_claims = array();
        $wd_item_statements = $wd_item->getStatements();

        foreach( $wd_item_statements as $statement ) {
            $claim = $statement->getClaim();
            $pid = $claim->getPropertyId();
            $value= $claim->getMainSnak()->getDataValue();
            $valueType = $value->getDataValueType();
            $numericEntityID = $value->getValue()->getEntityId()->getNumericId();

            $wd_item_claims[] = [ "$pid" => array("$numericEntityID", "$valueType", "$value") ];
        }
        return $wd_item_claims;
    }

    function get_json_response($url){
        $response = file_get_contents($url);
        $json_response = json_decode($response, true);
        return $json_response;
    }

    function crosscheck_items($wd_property_id, Array $wd_item_claims, Array $mb_item_json, Array $mapping) {
        /*global $mapping;*/
        global $result;

        $wd_validatable_properties = array_keys($mapping[$wd_property_id]["property_mapping"]); //Example: Array containing 569
        $wd_validatable_claims = array();

        foreach ($wd_validatable_properties as $property_id) {
            if (in_array("$property_id", array_keys($wd_item_claims))) {
                $wd_validatable_claims["$property_id"] = "$wd_item_claims[$property_id]";
            }
        }

        $wd_validatable_claims_data = $this->load_referenced_items_data($wd_validatable_claims);


        foreach (array_keys($wd_validatable_claims_data) as $wd_property_id){
            //database lookup of property
            $db_lookup_data = array();
            $wd_property_data = $wd_validatable_claims_data[$wd_property_id];

            /* array_diff(array1, array2)
             * Vergleicht array1 mit array2 und
             * gibt die Werte aus array1 zurück,
             * die nicht in array2 enthalten sind.
             */
            /*
            if (sizeof(array_diff($db_lookup_data,$wd_property_data)) = 0){
                foreach ($db_lookup_data as $db_lookup_data_item){
                    $result[] = "$wd_property_id" . " could be validated!";             //TODO decide what we want to print out
                }
            }
            else {
                $result[] = "$wd_property_id" . "could not be validated!";
            }*/
        }
    }

    function load_referenced_items_data(Array $wd_validatable_claims){
        $wd_validatable_claims_data = array();

        $lookup = WikibaseRepo::getDefaultInstance()->getStore()->getEntityLookup();    //TODO make global
        foreach (array_keys($wd_validatable_claims) as $wd_property_id){
            $item_id = new ItemId( "Q" . $wd_validatable_claims[$wd_property_id][0] );
            $entity = $lookup->getEntity($item_id);
            $aliases = $entity->getAliases("en");
            $label = array($entity->getLabel("en"));                                    //cast to array for merging in next line
            $wd_validatable_claims_data[$wd_property_id]= array_merge($aliases, $label);
        }
    return $wd_validatable_claims_data;
    }


}