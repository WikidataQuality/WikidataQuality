from wikidata.claimdict import ClaimDict


class Entity:
	def __init__(self, entity_id, entity_type=None, label=None, aliases=None, claims=None):
		self.entity_id = entity_id
		self.entity_type = entity_type
		self.label = label
		self.aliases = aliases
		self.claims = claims


	@staticmethod
	def parse(entity_dict, language):
		# Get entity id
		if "id" in entity_dict:
			entity_id = entity_dict["id"]
		else:
			entity_id = None

		# Get entity entitiy type
		if "type" in entity_dict:
			entity_type = entity_dict["type"]
		else:
			entity_type = None

		# Get label, if existent
		if "labels" in entity_dict and language in entity_dict["labels"]:
			label = entity_dict["labels"][language]["value"]
		else:
			label = None

		# Get aliases
		if "aliases" in entity_dict and language in entity_dict["aliases"]:
			aliases = [alias_json["value"] for alias_json in entity_dict["aliases"][language]]
		else:
			aliases = None

		# Get claims
		if "claims" in entity_dict:
			claims = ClaimDict.parse(entity_dict["claims"], language)
		else:
			claims = None

		return Entity(entity_id, entity_type, label, aliases, claims)


	def terms(self):
		if not self.label and self.aliases:
			return set(self.aliases)
		elif not self.aliases and self.label:
			return set([self.label])
		elif self.label and self.aliases:
			return set([self.label] + self.aliases)
		else:
			None