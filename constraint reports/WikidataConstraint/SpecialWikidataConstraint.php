<?php
class SpecialWikidataConstraint extends SpecialPage {
	function __construct() {
		parent::__construct( 'WikidataConstraint' );
	}
 
	function execute( $par ) {
		global $wgRequest, $wgOut;
		$this->setHeaders();
		
		$dbr = wfGetDB( DB_SLAVE );
		
		$res = $dbr->select(
			'page',									// $table
			array( 'page_title' ),					// $vars (columns of the table)
			'page_namespace = 122',					// $conds
			__METHOD__,								// $fname = 'Database::select',
			array( 'ORDER BY' => 'page_title ASC' )	// $options = array()
		);
		$output = '';
		
		foreach( $res as $row ) {
			$output .= '[[Property:' . $row->page_title . "]]\n\n";
		}
 
		$wgOut->addWikiText( $output );
	}
}