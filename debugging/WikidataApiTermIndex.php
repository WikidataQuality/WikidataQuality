<?php

namespace  Wikibase\Repo;

use BadMethodCallException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\SqlStore;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;
use Wikibase\TermSqlIndex;

/**
 * Class WikidataApiTermIndex
 * @package Wikibase\Repo
 * @author BP2014N1
 * @licence GNU GPL v2+
 */
class WikidataApiTermIndex extends WikidataApiLookup implements TermIndex, LabelConflictFinder {

	/**
	 * @var TermIndex|LabelConflictFinder
	 */
	private $fallbackTermIndex;

	/**
	 * @param TermIndex|LabelConflictFinder $fallbackTermIndex
	 */
	public function __construct( $fallbackTermIndex ) {
		parent::__construct();
		$this->fallbackTermIndex = $fallbackTermIndex;
	}

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
		$this->fallbackTermIndex->saveTermsOfEntity( $entity );
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
		$this->fallbackTermIndex->deleteTermsOfEntity( $entityId );
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
	 * @return TermIndexEntry[]
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
		if( in_array( TermIndexEntry::TYPE_LABEL, $termTypes ) ) {
			$parameter['props'][] = 'labels';
		}
		if( in_array( TermIndexEntry::TYPE_ALIAS, $termTypes ) ) {
			$parameter['props'][] = 'aliases';
		}
		if( in_array( TermIndexEntry::TYPE_DESCRIPTION, $termTypes ) ) {
			$parameter['props'][] = 'descriptions';
		}
		$parameter['props'] = implode( '|', $parameter['props'] );

		$entity = $this->requestEntity( $entityId, $parameter );
		if( $entity ) {
			$terms = array();
			if( in_array( TermIndexEntry::TYPE_LABEL, $termTypes ) ) {
				$labels = array();
				if( $languageCodes ) {
					foreach ( $languageCodes as $languageCode ) {
						try {
							$labels[ $languageCode ] = $entity->getFingerprint()->getLabel( $languageCode );
						}
						catch( \OutOfBoundsException $ex ) {}
					}
				}
				else {
					$labels = $entity->getFingerprint()->getLabels();
				}
				foreach ( $labels as $languageCode => $label ) {
					$terms[] = new TermIndexEntry(
						array(
							'termType' => TermIndexEntry::TYPE_LABEL,
							'termText' => $label->getText(),
							'termLanguage' => $languageCode
						)
					);
				}
			}
			if( in_array( TermIndexEntry::TYPE_ALIAS, $termTypes ) ) {
				$aliases = $entity->getAllAliases( $languageCodes );
				foreach ( $aliases as $languageCode => $aliasesOfLanguage ) {
					foreach ( $aliasesOfLanguage as $alias ) {
						$terms[] = new TermIndexEntry(
							array(
								'termType' => TermIndexEntry::TYPE_ALIAS,
								'termText' => $alias,
								'termLanguage' => $languageCode
							)
						);
					}
				}
			}
			if( in_array( TermIndexEntry::TYPE_DESCRIPTION, $termTypes ) ) {
				$descriptions = $entity->getDescriptions( $languageCodes );
				foreach ( $descriptions as $languageCode => $description ) {
					$terms[] = new TermIndexEntry(
						array(
							'termType' => TermIndexEntry::TYPE_DESCRIPTION,
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
	 * @return TermIndexEntry[]
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
	 * @param TermIndexEntry[] $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none
	 *
	 * @return TermIndexEntry[]
	 */
	public function getMatchingTerms(
		array $terms,
		$termType = null,
		$entityType = null,
		array $options = array()
	) {
		$this->fallbackTermIndex->getMatchingTerms( $terms, $termType, $entityType, $options );
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
	 * @param TermIndexEntry[] $terms
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
		$this->fallbackTermIndex->getMatchingIDs( $terms, $entityType, $options );
	}

	/**
	 * Clears all terms from the cache.
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear() {
		$this->fallbackTermIndex->clear();
	}


	/**
	 * Returns a list of Terms that conflict with (that is, match) the given labels.
	 * Conflicts are defined to be inside on type of entity and language.
	 * If $aliases is not null (but possibly empty), conflicts between aliases and labels
	 * are also considered.
	 *
	 * @note: implementations must return *some* conflicts if there are *any* conflicts,
	 * but are not required to return *all* conflicts.
	 *
	 * @param string $entityType The entity type to consider for conflicts.
	 * @param string[] $labels The labels to look for, with language codes as keys.
	 * @param string[][]|null $aliases The aliases to look for, with language codes as keys. If null,
	 *        conflicts with aliases are not considered.
	 *
	 * @return TermIndexEntry[]
	 */
	public function getLabelConflicts( $entityType, array $labels, array $aliases = null ) {
		$this->fallbackTermIndex->getLabelConflicts( $entityType, $labels, $aliases );
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
	 * @return TermIndexEntry[]
	 */
	public function getLabelWithDescriptionConflicts( $entityType, array $labels, array $descriptions ) {
		$this->fallbackTermIndex->getLabelWithDescriptionConflicts( $entityType, $labels, $descriptions );
	}
}