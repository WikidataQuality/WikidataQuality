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

		$id = new ItemId( $par );
		$lookup = WikibaseRepo::getDefaultInstance()->getStore()->getEntityLookup();
		$entity = $lookup->getEntity($id);
		$statements = $this->suggestByItem( $entity );


		$out->addWikiText( $statements );



		/*$out->addModules( 'ext.WikidataExternalDB' );
		$out->addWikiMsg( 'wikidataconstraint-summary' );
		$out->addHTML( '<p>Just enter an entity and let it crosscheck against MusicBrainz.<br/>'
			. 'Try for example <i>Qxx</i> (John Lennon) and <i>Qxx</i> (Imagine)'
			. ' and look at the results.</p>'
		);

		$out->addHTML( "<input placeholder='Qxx' id='item-input' autofocus>" );
		$out->addHTML( "<input type='button' value='Cross-check' id='check-item-btn'> </input>" );
		$out->addHTML( "<p/>" );
		$out->addHTML( "<ul id='results-list'></ul>" );
		$out->addHTML( "<p/>" );
		$out->addHTML( "<div id='result'></div>" );
		*/
	}

	function suggestByItem( Item $item) {
		$snaks = $item->getStatements()->getAllSnaks();
		foreach ( $snaks as $snak ) {
			return $snak->getType();
		}
		return;
	}
}