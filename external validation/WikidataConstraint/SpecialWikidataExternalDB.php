<?php

//include 'dbchecker/SimpleDBChecker.php'; not implemented yet

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Repo\Store;
use Wikibase\DataModel\Statement;
use Wikibase\DataModel\Snak;

class SpecialWikidataExternalDB extends SpecialPage {
	function __construct() {
		parent::__construct( 'WikidataExternalDB', '', true );
	}
 
	function execute( $par ) {
		$this->setHeaders();

		$out = $this->getContext()->getOutput();

		//$out->addModules( 'ext.WikidataExternalDB' );
		$out->addWikiMsg( 'wikidataconstraint-summary' );
		$out->addHTML( '<p>Just enter an entity and let it crosscheck against MusicBrainz.<br/>'
			. 'Try for example <i>Qxx</i> (John Lennon) and <i>Qxx</i> (Imagine)'
			. ' and look at the results.</p>'
		);

		$out->addHTML( "<form name='ItemIdForm' action='" . $_SERVER['PHP_SELF'] . "' method='post'>" );
		$out->addHTML( "<input placeholder='Qxx' name='itemId' id='item-input'>" );
		$out->addHTML( "<input type='submit' value='Cross-check' id='check-item-btn' />" );
		$out->addHTML( "</form>" );
		/*$out->addHTML( "<p/>" );
		$out->addHTML( "<ul id='results-list'></ul>" );
		$out->addHTML( "<p/>" );
		$out->addHTML( "<div id='result'></div>" );*/

		if (isset($_POST['itemId'])) {
			$id = new ItemId( $_POST['itemId'] );
			$lookup = WikibaseRepo::getDefaultInstance()->getStore()->getEntityLookup();
			$entity = $lookup->getEntity($id);
			$statementIds = $this->getStatementIds( $entity );

			foreach ( $statementIds as $id) {
				$out->addWikiText( $id );
			}
		}
	}

	function getStatementIds( Item $item ) {
		$snaks = $item->getStatements()->getAllSnaks();
		$numericIds = array();
		foreach ( $snaks as $snak ) {
			$numericIds[] = $snak->getPropertyId()->getNumericId();
		}
		return $numericIds;
	}
}