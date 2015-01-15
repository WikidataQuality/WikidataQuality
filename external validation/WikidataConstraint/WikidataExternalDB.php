<?php
# Alert the user that this is not a valid access point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/WikidataConstraint/WikidataExternalDB.php" );
EOT;
	exit( 1 );
}
 
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'WikidataConstraint',
	'author' => 'Andreas Burmeister, Jonas Keutel',
	'url' => 'https://www.mediawiki.org/wiki/Extension:WikidataConstraint',
	'descriptionmsg' => 'wikidataconstraint-desc',
	'version' => '0.0.0',
);

$wgResourceModules['ext.WikidataExternalDB'] = array(
	// JavaScript and CSS styles. To combine multiple files, just list them as an array.
	'scripts' => array( 'modules/ext.WikidataExternalDB.js' ),
	'styles' => 'modules/ext.WikidataExternalDB.css',
	'localBasePath' => __DIR__,
	// ... and the base from the browser as well. For extensions this is made easy,
	// you can use the 'remoteExtPath' property to declare it relative to where the wiki
	// has $wgExtensionAssetsPath configured:
	'remoteExtPath' => 'WikidataConstraint'
);
 
$wgAutoloadClasses['SpecialWikidataExternalDB'] = __DIR__ . '/SpecialWikidataExternalDB.php'; # Location of the SpecialWikidataExternalDB class (Tell MediaWiki to load this file)
$wgMessagesDirs['WikidataExternalDB'] = __DIR__ . "/i18n"; # Location of localisation files (Tell MediaWiki to load them)
$wgExtensionMessagesFiles['WikidataExternalDBAlias'] = __DIR__ . '/WikidataConstraint.alias.php'; # Location of an aliases file (Tell MediaWiki to load it)
$wgSpecialPages['WikidataExternalDB'] = 'SpecialWikidataExternalDB'; # Tell MediaWiki about the new special page and its class name