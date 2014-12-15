import requests
import math
from unidecode import unidecode
try:
    import ujson as json
except ImportError:
    import json as json

from wikidata.entitydict import EntityDict
from wikidata.claimdict import ClaimDict
from wikidata.helpers import *


class WikidataApi:

	# Number of maximum entities that can be requested through the Wikidata API
	WD_API_MAX_ENTITIES_PER_REQUEST = 50

	WD_API_LANGUAGE = "en"

	# Wikidata API endpoint to get english labels and aliases of an entity as JSON
	WD_API_ENTITIES_URL = "https://www.wikidata.org/w/api.php?action=wbgetentities&languages=%s&format=json" % WD_API_LANGUAGE

	# Wikidata API endpoint to get claims of an item as JSON
	WD_API_CLAIMS_URL = "https://www.wikidata.org/w/api.php?action=wbgetclaims&format=json"


	@staticmethod
	def get_claims(**request_params):
		# Build request url
		request_url = WikidataApi.WD_API_CLAIMS_URL
		for key in request_params.keys():
			query_string = "&%s=%s" % (key, request_params[key])
			request_url += query_string

		# Send request
		claims_dict = WikidataApi.__get_json_response(request_url)

		# Parse claims
		return ClaimDict.parse(claims_dict["claims"], WikidataApi.WD_API_LANGUAGE)


	# TODO check limits for other parameters
	@staticmethod
	def get_entities(**request_params):
		entities = {}
		if "ids" in request_params:
			ids_list = request_params["ids"].split("|")
			number_of_api_requests = math.ceil(len(ids_list) / WikidataApi.WD_API_MAX_ENTITIES_PER_REQUEST)	
			for i in range(0, number_of_api_requests):
				start_index = i * WikidataApi.WD_API_MAX_ENTITIES_PER_REQUEST
				end_index = start_index + WikidataApi.WD_API_MAX_ENTITIES_PER_REQUEST
				request_params["ids"] = "|".join(ids_list[start_index:end_index])

				# Build request url
				request_url = WikidataApi.WD_API_ENTITIES_URL
				for key in request_params.keys():
					query_string = "&%s=%s" % (key, request_params[key])
					request_url += query_string

				# Send request
				entities_dict = WikidataApi.__get_json_response(request_url)

				# Parse entities
				entities = unite_dictionaries(entities, EntityDict.parse(entities_dict["entities"], WikidataApi.WD_API_LANGUAGE))
		return entities


	@staticmethod
	def __get_json_response(url):
		resp = requests.get(url)
		resp_text = unidecode(resp.text)
		return json.loads(resp_text)