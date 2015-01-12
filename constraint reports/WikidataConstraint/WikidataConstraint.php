<?php
# Alert the user that this is not a valid access point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/WikidataConstraint/WikidataConstraint.php" );
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
 
$wgAutoloadClasses['SpecialWikidataConstraint'] = __DIR__ . '/SpecialWikidataConstraint.php'; # Location of the SpecialWikidataConstraint class (Tell MediaWiki to load this file)
$wgMessagesDirs['WikidataConstraint'] = __DIR__ . "/i18n"; # Location of localisation files (Tell MediaWiki to load them)
$wgExtensionMessagesFiles['WikidataConstraintAlias'] = __DIR__ . '/WikidataConstraint.alias.php'; # Location of an aliases file (Tell MediaWiki to load it)
$wgSpecialPages['WikidataConstraint'] = 'SpecialWikidataConstraint'; # Tell MediaWiki about the new special page and its class name