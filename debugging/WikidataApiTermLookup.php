<?php

namespace Wikibase\Repo;


use Wikibase\Lib\Store\TermLookup;
use Wikibase\DataModel\Entity\EntityId;


/**
 * Class WikidataApiTermLookup
 * @package Wikibase\Repor
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
        return $entity->getFingerprint()->getLabel( $languageCode )->getText();
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
        return $entity->getFingerprint()->getLabels( $languageCodes )->toTextArray();
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
        return $entity->getFingerprint()->getDescription( $languageCode )->getText();
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
        return $entity->getFingerprint()->getDescriptions( $languageCodes )->toTextArray();
    }
}