import requests
from objectpath import *
from types import *
from termcolor import colored
from unidecode import unidecode
try:
    import ujson as json
except ImportError:
    import json as json

from wikidata.api import *


# Dictionary, that maps Wikidata properties to MusicBrainz properties. 
# Keys are those Wikidata properties, which represent MusicBrainz identifier. The keys contain the appropriate MusicBrainz API endpoint and the concrete mapping of the properties for this MusicBrainz entity.
# TODO: Get mapping from statements on properties
mapping = {
	# Artist
	"P434": {
		"mb_url": "http://musicbrainz.org/ws/2/artist/%s?inc=artist-rels&fmt=json", #Example: Q1203
		"property_mapping": {
			"P569": "$.\"life-span\".begin", # date of birth
			"P570": "$.\"life-span\".end", # date of death
			"P19": "$.begin_area.name", # place of birth
			"P20": "$.end_area.name", # place of death
			"P27": "$.area.name", # country of citizenship
			"P40": "$.relations[@.type is 'parent' and @.direction is 'forward'].artist.name", # child
			"P26": "$.relations[@.type is 'married' and @.direction is 'forward'].artist.name", # spouse
			"P463": "$.relations[@.type is 'member of band' and @.direction is 'forward'].artist.name", # member of
			"P527": "$.relations[@.type is 'member of band' and @.direction is 'backward'].artist.name" # has part
		}
	},
	# Work
	"P435": {
		"mb_url": "http://musicbrainz.org/ws/2/work/%s?inc=artist-rels+label-rels+work-rels&fmt=json", #Example: Q1971
		"property_mapping": {
			"P676": "$.relations[@.type is 'lyricist' and @.direction is 'backward'].artist.name", # lyrics by
			"P86": "$.relations[@.type is 'composer' and @.direction is 'backward'].artist.name", # composer
			"P264": "$.relations[@.type is 'publishing' and @.direction is 'backward'].label.name", # record label
			"P144": "$.relations[@.type is 'based on'].work.name", # based on
			"P407": "$.language" # language
		}
	},
	# Release-Group
	"P436": {
		"mb_url": "http://musicbrainz.org/ws/2/release-group/%s?inc=artist-credits&fmt=json", #Example: Q3192
		"property_mapping": {
			"P175": "$.\"artist-credit\".artist.name", # performer
			"P577": "$.\"first-release-date\"" # date of publication
		}
	},
	# Label
	"P966": {
		"mb_url": "http://musicbrainz.org/ws/2/label/%s?fmt=json", #Example: Q21077
		"property_mapping": {
			"P159": "$.area.name", # headquarters location
			"P17": "$.country", # country
			"P571": "$.\"life-span\".begin", # date of foundation or creation
			"P576": "$.\"life-span\".end", # date of dissolution
			"P355": "$.relations[@.type is 'label ownership' and @.direction is 'forward'].label.name", # subsidiaries
			"P749": "$.relations[@.type is 'label ownership' and @.direction is 'backward'].label.name" # parent company
		}
	},
	# Area
	"P982": {
		"mb_url": "http://musicbrainz.org/ws/2/area/%s?inc=area-rels&fmt=json", #Example: Q30
		"property_mapping": {
			"P150": "$.relations[@.type is 'part of' and @.direction is 'forward'].area.name" # contains administrative territorial entity
		}
	}
}


# Validates the specified Wikidata item with aid of MusicBrainz, if identifier is given
def validate(wd_item_id):
	# Get items data as json
	wd_claims = WikidataApi.get_claims(entity=wd_item_id)

	# Validate item for each identifier property, that can be used for validation
	for wd_identifier_property in mapping.keys():
		if wd_identifier_property in wd_claims:
			# Get mapped MusicBrainz item
			mb_identifier = wd_claims[wd_identifier_property][0].data_value.value
			mb_url = mapping[wd_identifier_property]["mb_url"] % mb_identifier
			mb_item = get_json_response(mb_url)

			# Validate these two items
			crosscheck_items(wd_identifier_property, wd_claims, mb_item)


# Send GET-request to the specified url and returns the response as a json object
def get_json_response(url):
	resp = requests.get(url)
	json_resp = resp.json()
	return json_resp


# Crosscheck given Wikidata claims with MusicBrainz item
def crosscheck_items(wd_identifier_property, wd_claims, mb_item):
	wd_validatable_properties = mapping[wd_identifier_property]["property_mapping"].keys()
	wd_validatable_claims = wd_claims.filter(wd_validatable_properties)
	wd_validatable_claims.load_referenced_items(props="labels|aliases")
	mb_item_tree = Tree(mb_item)
	
	for wd_data_property in wd_validatable_claims.keys():
		# Get MusicBrainz values
		mb_property_values = get_mb_property_values(wd_identifier_property, mb_item_tree, wd_data_property)
		if not mb_property_values:
			continue

		# Get Wikidata values
		wd_claims_per_property = wd_validatable_claims[wd_data_property]
		for wd_claim in wd_claims_per_property:
			# Compare two values a print result
			if wd_claim.data_value:
				if wd_claim.data_value.compare_to(mb_property_values): 
					print(colored("Property %s could be validated!" % wd_data_property, "green")) 
				else:
					print(colored("Property %s could not be validated!" % wd_data_property, "red"))

				print("Wikidata: %s <--> MusicBrainz: %s" % (str(wd_claim.data_value), mb_property_values))
				print()


# Extracts the MusicBrainz properties mapped from the given Wikidata property value
def get_mb_property_values(wd_identifier_property, mb_item_tree, wd_data_property):
	mb_mapped_objectpath = mapping[wd_identifier_property]["property_mapping"][wd_data_property]
	mb_query_result = mb_item_tree.execute(mb_mapped_objectpath)
	if mb_query_result:
		mb_property_values = []
		if type(mb_query_result) is str:
			mb_property_values.append(unidecode(mb_query_result))
		elif type(mb_query_result) is GeneratorType:
			for mb_property_value in mb_query_result:
				mb_property_values.append(unidecode(mb_property_value))
		else:
			return None
	else:
		return None
	return mb_property_values