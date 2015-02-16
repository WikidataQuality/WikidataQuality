Debugging
===============

## Working with test data using the ```WikidataApiEntityLookup```
Instead of importing or creating entites, the ```WikidataEntityLookup``` can be easily used for accessing Wikidata entities by using the API.

### Installation
1. Copy/Overwrite the files ```WikidataApiEntityLookup.php``` and ```WikibaseRepo.php``` to ```/extensions/Wikidata/extensions/Wikibase/repo/includes```.
2. Run ```composer update``` in ```/extensions/Wikidata```. If composer asks you about changes, stash them by typing ```s```.

### Usage
To enable the ```WikidataApiEntityLookup```, you have to set the following constant
```php
define("USE_WIKIDATA_API_LOOKUP", true);
```
After that each time you call the function
```php
WikibaseRepo::getDefaultInstance()->getEntityLookup()
````
you will get a instance of ```WikidataApiEntityLookup``` instead the conventional ```EntityLookup```.
