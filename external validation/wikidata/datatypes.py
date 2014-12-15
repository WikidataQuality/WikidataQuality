import re
import datetime


class DataType:
	@staticmethod
	def parse(data_mainsnak_dict, language):
		data_type = data_mainsnak_dict["datatype"]
		data_value_dict = data_mainsnak_dict["datavalue"]
		if data_type == "wikibase-item":
			return WikibaseItem.parse(data_value_dict)
		elif data_type == "time":
			return Time.parse(data_value_dict)
		elif data_type == "string":
			return String.parse(data_value_dict)
		elif data_type == "url":
			return Url.parse(data_value_dict)
		elif data_type == "monolingualtext":
			return MonolingualText.parse(data_value_dict, language)
		elif data_type == "globe-coordinate":
			return GlobeCoordinate.parse(data_value_dict)
		else:
			return None



class WikibaseItem:
	def __init__(self, entity_id, item=None):
		# Import Entity locally to prevent circular imports
		from wikidata.entity import Entity

		self.entity_id = entity_id
		if item:
			self.item = item
		else:
			self.item = Entity(entity_id)


	@staticmethod
	def parse(data_value_dict):
		entity_id = "Q%s" % data_value_dict["value"]["numeric-id"]
		return WikibaseItem(entity_id)


	def __str__(self):
		return str(self.item.terms())


	def compare_to(self, counterpart_values):
		terms = self.item.terms()
		if terms:
			if len(self.item.terms().intersection(counterpart_values)) > 0:
				return True
			else:
				return False
		else:
			return True



class Time:
	def __init__(self, time, timezone, before, after, precision, calendar_model):
		self.time = time
		self.timezone = timezone
		self.before = before
		self.after = after
		self.precision = precision
		self.calendar_model = calendar_model


	@staticmethod
	def parse(data_value_dict):
		time = data_value_dict["value"]["time"]
		timezone = data_value_dict["value"]["timezone"]
		before = data_value_dict["value"]["before"]
		after = data_value_dict["value"]["after"]
		precision = data_value_dict["value"]["precision"]
		calendar_model = data_value_dict["value"]["calendarmodel"]
		return Time(time, timezone, before, after, precision, calendar_model)


	def __str__(self):
		return self.time


	def compare_to(self, counterpart_values):
		# Convert ISO8601 date to datetime and return iso format
		splitted_date = re.findall("([+-])(\d+)\-(\d+)\-(\d+)T(\d+)\:(\d+)\:(\d+)Z", self.time) # Check, if mb is complete date or only year e.g.
		year = int(splitted_date[0][0] + splitted_date[0][1])
		month = int(splitted_date[0][2])
		day = int(splitted_date[0][3])
		date = datetime.date(year, month, day)

		# Compare
		if date.isoformat() in counterpart_values:
			return True
		else:
			return False



class String:
	def __init__(self, value):
		self.value = value


	def __str__(self):
		return self.value


	@staticmethod
	def parse(data_value_dict):
		value = data_value_dict["value"]
		return String(value)


	def compare_to(self, counterpart_values):
		if self.value in counterpart_values:
			return True
		else:
			return False



class Url:
	def __init__(self, url):
		self.url = url


	def __str__(self):
		return self.url


	@staticmethod
	def parse(data_value_dict):
		url = data_value_dict["value"]
		return Url(url)


	def compare_to(self, counterpart_values):
		if self.url in counterpart_values:
			return True
		else:
			return False



class MonolingualText:
	def __init__(self, text):
		self.text = text


	def __str__(self):
		return self.text


	@staticmethod
	def parse(data_value_dict, language):
		if data_value_dict["value"]["language"] == language:
			text = data_value_dict["value"]["text"]
			return MonolingualText(text)
		else:
			return MonolingualText(None)


	def compare_to(self, counterpart_values):
		if self.text and self.text in counterpart_values:
			return True
		else:
			return False



class Quantity:
	def __init__(self, ammount, unit, upperbound, lowerbound):
		self.ammount = ammount
		self.unit = unit
		self.upperbound = upperbound
		self.lowerbound = lowerbound


	def __str__(self):
		return sef.ammount


	@staticmethod
	def parse(data_value_dict):
		ammount = data_value_dict["value"]["ammount"]
		unit = data_value_dict["value"]["unit"]
		upperbound = data_value_dict["value"]["upperbound"]
		lowerbound = data_value_dict["value"]["lowerbound"]


	def compare_to(self, counterpart_values):
		float_ammount = float(self.ammount)
		if float_ammount in [float(value) for value in counterpart_values]:
			return True
		else:
			return False


class GlobeCoordinate:
	def __init__(self, latitude, longitude, globe, precision):
		self.latitude = latitude
		self.longitude = longitude
		self.globe = globe
		self.precision = precision


	def __str__(self):
		return "%s, %s" % (self.latitude, self.longitude)


	@staticmethod
	def parse(data_value_dict):
		latitude = data_value_dict["value"]["latitude"]
		longitude = data_value_dict["value"]["longitude"]
		globe = data_value_dict["value"]["globe"]
		precision = data_value_dict["value"]["precision"]
		return GlobeCoordinate(latitude, longitude, globe, precision)


	def compare_to(self, counterpart_values):
		raise Exception("not implementet yet.")
