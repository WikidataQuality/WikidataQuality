<?php

namespace Wikibase\Repo;


use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookup;


/**
 * Class WikidataApiTermLookup
 * @package Wikibase\Repo
 * @author BP2014N1
 * @licence GNU GPL v2+
 */
class WikidataApiTermLookup extends WikidataApiLookup implements TermLookup
{
    public function getLabel( EntityId $entityId, $languageCode )
    {
        $entity = $this->requestEntity(
            $entityId,
            array(
                'languages' => $languageCode,
                'props' => 'labels'
            )
        );
        if( $entity ) {
            return $entity->getFingerprint()->getLabel( $languageCode )->getText();
        }
    }

    public function getLabels( EntityId $entityId, array $languageCodes )
    {
        $entity = $this->requestEntity(
            $entityId,
            array(
                'languages' => implode( '|', $languageCodes ),
                'props' => 'labels'
            )
        );
        if( $entity ) {
            return $entity->getFingerprint()->getLabels( $languageCodes )->toTextArray();
        }
    }

    public function getDescription( EntityId $entityId, $languageCode )
    {
        $entity = $this->requestEntity(
            $entityId,
            array(
                'languages' => $languageCode,
                'props' => 'descriptions'
            )
        );
        if( $entity ) {
            return $entity->getFingerprint()->getDescription( $languageCode )->getText();
        }
    }

    public function getDescriptions( EntityId $entityId, array $languageCodes )
    {
        $entity = $this->requestEntity(
            $entityId,
            array(
                'languages' => implode( '|', $languageCodes ),
                'props' => 'descriptions'
            )
        );
        if( $entity ) {
            return $entity->getFingerprint()->getDescriptions( $languageCodes )->toTextArray();
        }
    }
}