<?php

include 'CrossChecker.php';

class SpecialWikidataExternalDB extends SpecialPage {
	function __construct() {
		parent::__construct( 'WikidataExternalDB', '', true );
	}
 
	function execute( $par ) {
		$this->setHeaders();
		$out = $this->getContext()->getOutput();

		// $out->addModules( 'ext.WikidataExternalDB' );
		$out->addWikiMsg( 'wikidataconstraint-summary' );
		$out->addHTML( '<p>Just enter an entity and let it crosscheck against MusicBrainz.<br/>'
			. 'Try for example <i>Qxx</i> (John Lennon) and <i>Qxx</i> (Imagine)'
			. ' and look at the results.</p>'
		);

		$out->addHTML( "<form name='ItemIdForm' action='" . $_SERVER['PHP_SELF'] . "' method='post'>" );
		$out->addHTML( "<input placeholder='Qxx' name='itemId' id='item-input'>" );
		$out->addHTML( "<input type='submit' value='Cross-check' id='check-item-btn' />" );
		$out->addHTML( "</form>" );

		if (isset($_POST['itemId'])) {
			$checker = new CrossChecker();

			/* crosscheck item */
			$result = $checker->crosscheckItem( $_POST['itemId'] );

			/* result output
			foreach ( $result as $res) {
				$out->addWikiText( $res );
			}*/
		}
	}


}