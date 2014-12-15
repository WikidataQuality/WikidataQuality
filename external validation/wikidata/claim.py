from wikidata.datatypes import DataType


class Claim:
	def __init__(self, claim_id, property_id, data_type, data_value):
		self.claim_id = claim_id
		self.property_id = property_id
		self.data_type = data_type
		self.data_value = data_value


	@staticmethod
	def parse(claim_dict, language):
		# Get claim id
		claim_id = claim_dict["id"]

		# Get property id
		property_id = claim_dict["mainsnak"]["property"]

		# Get data value and type, if snak contains a value
		if claim_dict["mainsnak"]["snaktype"] == "value":
			# Get data type
			data_type = claim_dict["mainsnak"]["datatype"]

			# Get data value
			data_value = DataType.parse(claim_dict["mainsnak"], language)
		else:
			data_type = None
			data_value = None

		return Claim(claim_id, property_id, data_type, data_value)