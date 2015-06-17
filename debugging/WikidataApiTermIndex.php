<?php

namespace  Wikibase\Repo;

use BadMethodCallException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\Term;
use Wikibase\TermIndex;

/**
 * Class WikidataApiTermIndex
 * @package Wikibase\Repo
 * @author BP2014N1
 * @licence GNU GPL v2+
 */
class WikidataApiTermIndex extends WikidataApiLookup implements TermIndex, LabelConflictFinder {

	/**
	 * Saves the terms of the provided entity in the term cache.
	 *
	 * @since 0.1
	 *
	 * @param EntityDocument $entity
	 *
	 * @return boolean Success indicator
	 */
	public function saveTermsOfEntity( EntityDocument $entity ) {
		throw new BadMethodCallException( 'Wikidata API services are read-only.' );
	}

	/**
	 * Deletes the terms of the provided entity from the term cache.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 *
	 * @return boolean Success indicator
	 */
	public function deleteTermsOfEntity( EntityId $entityId ) {
		throw new BadMethodCallException( 'Wikidata API services are read-only.' );
	}

	/**
	 * Returns the terms stored for the given entity.
	 *
	 * @param EntityId $entityId
	 * @param string[]|null $termTypes The types of terms to return, e.g. "label", "description",
	 *        or "alias". Compare the Term::TYPE_XXX constants. If null, all types are returned.
	 * @param string[]|null $languageCodes The desired languages, given as language codes.
	 *        If null, all languages are returned.
	 *
	 * @return Term[]
	 */
	public function getTermsOfEntity(
		EntityId $entityId,
		array $termTypes = null,
		array $languageCodes = null
	) {
		$parameter = array();
		if( $languageCodes ) {
			$parameter['languages'] = implode( '|', $languageCodes );
		}
		if( in_array( Term::TYPE_LABEL, $termTypes ) ) {
			$parameter['props'][] = 'labels';
		}
		if( in_array( Term::TYPE_ALIAS, $termTypes ) ) {
			$parameter['props'][] = 'aliases';
		}
		if( in_array( Term::TYPE_DESCRIPTION, $termTypes ) ) {
			$parameter['props'][] = 'descriptions';
		}
		$parameter['props'] = implode( '|', $parameter['props'] );

		$entity = $this->requestEntity( $entityId, $parameter );
		if( $entity ) {
			$terms = array();
			if( in_array( Term::TYPE_LABEL, $termTypes ) ) {
				$labels = array();
				if( $languageCodes ) {
					foreach ( $languageCodes as $languageCode ) {
						$labels[$languageCode] = $entity->getFingerprint()->getLabel( $languageCode );
					}
				}
				else {
					$labels = $entity->getFingerprint()->getLabels();
				}
				foreach ( $labels as $languageCode => $label ) {
					$terms[] = new Term(
						array(
							'termType' => Term::TYPE_LABEL,
							'termText' => $label->getText(),
							'termLanguage' => $languageCode
						)
					);
				}
			}
			if( in_array( Term::TYPE_ALIAS, $termTypes ) ) {
				$aliases = $entity->getAllAliases( $languageCodes );
				foreach ( $aliases as $languageCode => $aliasesOfLanguage ) {
					foreach ( $aliasesOfLanguage as $alias ) {
						$terms[] = new Term(
							array(
								'termType' => Term::TYPE_ALIAS,
								'termText' => $alias,
								'termLanguage' => $languageCode
							)
						);
					}
				}
			}
			if( in_array( Term::TYPE_DESCRIPTION, $termTypes ) ) {
				$descriptions = $entity->getDescriptions( $languageCodes );
				foreach ( $descriptions as $languageCode => $description ) {
					$terms[] = new Term(
						array(
							'termType' => Term::TYPE_DESCRIPTION,
							'termText' => $description,
							'termLanguage' => $languageCode
						)
					);
				}
			}

			return $terms;
		}
	}

	/**
	 * Returns the terms stored for the given entities. Can be filtered by language.
	 * Note that all entities queried in one call must be of the same type.
	 *
	 * @since 0.4
	 *
	 * @param EntityId[] $entityIds Entity ids of one type only.
	 * @param string[]|null $termTypes The types of terms to return, e.g. "label", "description",
	 *        or "alias". Compare the Term::TYPE_XXX constants. If null, all types are returned.
	 * @param string[]|null $languageCodes The desired languages, given as language codes.
	 *        If null, all languages are returned.
	 *
	 * @return Term[]
	 */
	public function getTermsOfEntities(
		array $entityIds,
		array $termTypes = null,
		array $languageCodes = null
	) {
		$terms = array();

		foreach( $entityIds as $entityId ) {
			$terms = array_merge( $terms, $this->getTermsOfEntity( $entityId, $termTypes, $languageCodes ) );
		}

		return $terms;
	}

	/**
	 * Returns the terms that match the provided conditions.
	 *
	 * $terms is an array of Term objects. Terms are joined by OR.
	 * The fields of the terms are joined by AND.
	 *
	 * A default can be provided for termType and entityType via the corresponding
	 * method parameters.
	 *
	 * The return value is an array of Terms where entityId, entityType,
	 * termType, termLanguage, termText are all set.
	 *
	 * @since 0.2
	 *
	 * @param Term[] $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none
	 *
	 * @return Term[]
	 */
	public function getMatchingTerms(
		array $terms,
		$termType = null,
		$entityType = null,
		array $options = array()
	) {
		throw new BadMethodCallException( 'Not implemented so far.' );
	}

	/**
	 * Returns the IDs that match the provided conditions.
	 *
	 * $terms is an array of Term objects. Terms are joined by OR.
	 * The fields of the terms are joined by AND.
	 *
	 * A single entityType has to be provided.
	 *
	 * @since 0.4
	 *
	 * @param Term[] $terms
	 * @param string|null $entityType
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none
	 *
	 * @return EntityId[]
	 */
	public function getMatchingIDs( array $terms, $entityType = null, array $options = array() ) {
		throw new BadMethodCallException( 'Not implemented so far.' );
	}

	/**
	 * Clears all terms from the cache.
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear() {
		throw new BadMethodCallException( 'There is nothing to clear.' );
	}


	/**
	 * Returns a list of Terms that conflict with (that is, match) the given labels.
	 * Conflicts are defined to be inside on type of entity and language.
	 *
	 * @note: implementations must return *some* conflicts if there are *any* conflicts,
	 * but are not required to return *all* conflicts.
	 *
	 * @param string $entityType The entity type to consider for conflicts.
	 * @param string[] $labels The labels to look for, with language codes as keys.
	 *
	 * @return Term[]
	 */
	public function getLabelConflicts( $entityType, array $labels ) {
		throw new BadMethodCallException( 'Not implemented so far.' );
	}

	/**
	 * Returns a list of Terms that conflict with (that is, match) the given labels
	 * and descriptions. Conflicts are defined to be inside on type of entity and one language.
	 * For a label to be considered a conflict, there must be a conflicting description on the
	 * same entity. From this it follows that labels with no corresponding description
	 * cannot contribute to a conflicts.
	 *
	 * @note: implementations must return *some* conflicts if there are *any* conflicts,
	 * but are not required to return *all* conflicts.
	 *
	 * @param string|null $entityType The relevant entity type
	 * @param string[] $labels The labels to look for, with language codes as keys.
	 * @param string[] $descriptions The descriptions to consider (if desired), with language codes as keys.
	 *
	 * @return Term[]
	 */
	public function getLabelWithDescriptionConflicts( $entityType, array $labels, array $descriptions ) {
		throw new BadMethodCallException( 'Not implemented so far.' );
	}
}