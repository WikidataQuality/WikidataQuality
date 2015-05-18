Debugging
===============

## Working with test data using the *Wikidata API lookups*.
Instead of importing or creating entites, the *Wikidata API lookups* can easily be used for accessing Wikidata entities by using the API.

### Installation
1. Make sure, that the Wikidata extension is up to date. Otherwise update it first by running ```git pull``` and ```composer update``` in ```/extensions/Wikidata```. 
2. Copy/Overwrite the files ```WikidataApiLookup.php```, ```WikidataApiTermLookup.php```, ```WikidataApiEntityLookup.php```, ```WikidataApiEntityRevisionLookup.php```, and ```WikibaseRepo.php``` to ```/extensions/Wikidata/extensions/Wikibase/repo/includes```.
3. Run ```composer update``` in ```/extensions/Wikidata```. If composer asks you about changes, stash them by typing ```s```.

### Usage
To enable the *Wikidata API lookups*, you have to set the following constant in ```/extensions/Wikidata/extensions/Wikibase/repo/Wikibase.php```
```php
if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
	define('USE_WIKIDATA_API_LOOKUP', true);
}
```