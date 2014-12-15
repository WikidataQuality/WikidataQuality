from wikidata.entity import Entity


class EntityDict(dict):
	@staticmethod
	def parse(entities_dict, language):
		entities = {}
		for entity_dict in entities_dict.values():
			entity = Entity.parse(entity_dict, language)
			entities[entity.entity_id] = entity
		return entities