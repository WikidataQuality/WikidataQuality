from wikidata.claim import Claim
from wikidata.datatypes import WikibaseItem


class ClaimDict(dict):
	@staticmethod
	def parse(claims_dict, language):
		claims = ClaimDict()
		for claim_property in claims_dict.keys():
			claims[claim_property] = []
			for claim_dict in claims_dict[claim_property]:
				claims[claim_property].append(Claim.parse(claim_dict, language))
		return claims


	def filter(self, property_ids):
		claims = ClaimDict()
		for property_id in self:
			if property_id in property_ids:
				claims[property_id] = self[property_id]
		return claims


	def load_referenced_items(self, recursive_depth=0, **request_params):
		# Import WikidataApi locally to prevent circular imports
		from wikidata.api import WikidataApi

		# Get referencing data values and the references item ids from the current entity
		referenced_item_ids = []
		referencing_data_values = []
		for claims in self.values():
			for claim in claims:
				if type(claim.data_value) is WikibaseItem:
					referencing_data_values.append(claim.data_value)
					referenced_item_ids.append(claim.data_value.entity_id)

		# Request referenced items
		request_params["ids"] = "|".join(referenced_item_ids)
		entities = WikidataApi.get_entities(**request_params)

		# Add requested item to the corresponding data value
		for data_value in referencing_data_values:
			data_value.item = entities[data_value.entity_id]

			# If recorsive depth is not reached yet, load referenced items
			if recursive_depth > 0:
				claim.data_value.entity.load_referenced_items(recursive_depth - 1, request_params)