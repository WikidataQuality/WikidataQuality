from lxml import html
import requests
from collections import OrderedDict
from operator import itemgetter
import time
import codecs
import unicodedata
import os
import json
import uuid

class sqlScriptBuilder:

	MAX_PROPERTY_NUMBER = 2000
	MAX_SQL_LINE_NUMBER = 455
	SQL_FILE_NAME = "fill_wdqa_constraints_from_templates_with_json_blob.sql"

	SQL_SCRIPT_HEAD = "INSERT INTO wdqa_constraints (constraint_guid, pid, constraint_type_qid, constraint_parameters) VALUES\n"

	parameters = {}

	def find_next_seperator(self, constraint_parameters, equal_sign):
		next_equal_sign = constraint_parameters.find('=', equal_sign + 1)
		if next_equal_sign == -1:
			next_seperator = len(constraint_parameters)
		else:
			next_seperator = constraint_parameters.rfind('|', equal_sign, next_equal_sign)
		if next_seperator == -1:
			next_seperator = len(constraint_parameters)
		else:
			next_seperator = next_seperator + 1
		return next_seperator

	def to_comma_seperated_string(self, values):
		return values.replace("{", "").replace("}", "").replace("|", "").replace(" ", "").replace("[", "").replace("]", "").strip()

	def add_property(self, values):
		self.parameters['property'] = values.strip()

	def add_classes(self, values):
		self.parameters['class'] = self.to_comma_seperated_string(values)

	def add_exceptions(self, values):
		self.parameters['known_exception'] = self.to_comma_seperated_string(values).replace(";", ",")

	def add_group_by(self, values):
		self.parameters['group_by'] = values.strip()

	def add_items(self, values):
		itemString = ""
		snakString = ""
		for element in self.to_comma_seperated_string(values).split(","):
			if element.startswith("Q"):
				itemString = itemString + element + ","
			elif element.lower() == "somevalue" or element.lower() == "novalue":
				snakString = snakString + element + ","
		if itemString != "":
			self.parameters['item'] = itemString.rstrip(",")
		if snakString != "":
			self.parameters['snak'] = snakString.rstrip(",")

	def add_list(self, values, constraint_name):
		if constraint_name == "Qualifiers" or constraint_name == "Mandatory qualifiers":
			self.parameters['property'] = self.to_comma_seperated_string(values)
		else:
			self.list_parameter = self.to_comma_seperated_string(values)

	def add_status(self, values):
		self.parameters['constraint_status'] = 'mandatory'

	def add_max(self, values):
		#if "0000" in values or "." in values:
		#	self.parameters['maximum date'] = values
		#else:
		self.parameters['maximum_quantity'] = values.strip()

	def add_min(self, values):
		#if "0000" in values or "." in values:
		#	self.parameters['minimum date'] = values
		#else:
		self.parameters['minimum_quantity'] = values.strip()

	def add_namespace(self, values):
		self.parameters['namespace'] = values.strip()

	def add_pattern(self, values):
		self.parameters['pattern'] = values.replace("\\","\\\\").strip()

	def add_relation(self, values):
		self.parameters['relation'] = values.strip()	

	def write_one_line(self, property_number, constraint_name):
		self.write_first_three_columns(property_number, constraint_name)
		self.write_blob()
		self.reset_parameter()

	def write_multiple_lines(self, property_number, constraint_name):
		for line in self.list_parameter.split(';'):
			self.write_first_three_columns(property_number, constraint_name)
			self.split_list_parameter(line)
			self.write_blob()	
			self.parameters.pop('item', None)
		self.reset_parameter

	def write_line_in_sql_file(self, property_number, constraint_name):
		if self.list_parameter != 'NULL':
			self.write_multiple_lines(property_number, constraint_name)
		else:
			self.write_one_line(property_number, constraint_name)

	def split_list_parameter(self, line):
		if ':' in line:
			self.parameters['item'] = line[line.index(':')+1:]
			self.parameters['property'] = line[:line.index(':')]
		else:
			self.parameters['property'] = line


	def write_first_three_columns(self, property_number, constraint_name):
		self.outputString += ( '(' + '"' + str(uuid.uuid4()) + '", ' + format(property_number) + ', \"' + constraint_name.strip() + '\", ' )

	def write_blob(self):
		json_blob_string = json.dumps(json.dumps(self.parameters)).replace("&lt;nowiki>","").replace("&lt;/nowiki>","").replace("&amp;lt;nowiki&amp;lt;","").replace("&amp;lt;/nowiki&amp;gt;","").replace("<nowiki>","").replace("</nowiki>","")
		self.outputString += json_blob_string
		self.outputString += ("),\n")

	def reset_parameter(self):
		self.parameters = {}
		self.list_parameter = 'NULL'

	def writeOutputStringToFile(self):
		print("writing into " + self.SQL_FILE_NAME)
		with codecs.open(self.SQL_FILE_NAME, "a", "utf-8") as sql_file:
			self.outputString = self.outputString.rstrip(",\n") + ";\n\n"
			sql_file.write(self.outputString)

		self.writtenLinesInInsertStatement = 0
		self.outputString = self.SQL_SCRIPT_HEAD

	def get_constraint_part(self, property_talk_page):
		start = property_talk_page.find("{{Constraint:")
		 	
		end = property_talk_page.find("==")
		if( end != -1):
			property_talk_page = property_talk_page[start:end]
		else:
			property_talk_page = property_talk_page[start:]

		#delete <!-- --> comments from site
		open_index = property_talk_page.find("&lt;!--")
		while (open_index) != -1:
			close_index = property_talk_page.find("-->", open_index)
			if(close_index == -1):
				break
				
			property_talk_page = property_talk_page[:open_index] + property_talk_page[close_index+3:]
			
			open_index = property_talk_page.find("&lt;!--")	
		return property_talk_page

	# only purpose: Build SQL-Statement to fill table with constraints
	# fetches constraints from property talk pages
	# nonetheless: use table layout that will suit the new way of storing 
	# constraints as statements on properties

	def run(self):
		# delete old file
		if os.path.exists(self.SQL_FILE_NAME):
			os.remove(self.SQL_FILE_NAME)

		self.writtenLinesInInsertStatement = 0
		self.outputString = self.SQL_SCRIPT_HEAD

		#this is how every constraint template begins
		search_string = "{{Constraint:"

		for property_number in range(1, self.MAX_PROPERTY_NUMBER+1):

			if (self.writtenLinesInInsertStatement > self.MAX_SQL_LINE_NUMBER):
				self.writeOutputStringToFile()


			#self.outputString += (30*"=" + "Property " + format(property_number) + 30*"=")
			if property_number % 10 == 0:
				print(format(property_number) + "/" + format(self.MAX_PROPERTY_NUMBER))
			property_talk_page = requests.get("http://www.wikidata.org/w/index.php?title=Property_talk:P" + str(property_number) + "&action=edit").text
			
			# check if property exists
			if property_talk_page.find("Creating Property talk") != -1:
				continue;

			#delete <nowiki> </nowiki> tags from property_talk_page 
			property_talk_page = self.get_constraint_part(property_talk_page)

			# indices for the first and last character belonging to a respective constraint
			start_index = end_index = None
			
			# find beginning of constraint
			start_index = property_talk_page.find(search_string)

			#as long as there are more constraints, set new start index and cut off everything before it
			while start_index != -1:
				start_index += len(search_string)
				property_talk_page = property_talk_page[start_index:]

				#match brackets to find end of constraint
				count = 2
				for i, c in enumerate(property_talk_page):
					if c == '{':
						count += 1
					elif c == '}':
						count -= 1
					if count == 0:
						end_index = i-1
						break
				
				#extract constraint
				constraint_string = property_talk_page[:end_index]

				
				constraint_name = None
				constraint_parameters = None
				self.list_parameter = 'NULL'

				delimiter_index = constraint_string.find('|')

				if delimiter_index == -1:
					constraint_name = constraint_string
				else:			
					constraint_name = constraint_string[:delimiter_index]
					constraint_parameters = constraint_string[delimiter_index+1:]

					while constraint_parameters != None and constraint_parameters.find('=') != -1:
						equal_sign = constraint_parameters.find('=')
						next_seperator = self.find_next_seperator(constraint_parameters, equal_sign)
						a = 0 if next_seperator == -1 else -1
						parameter_name = constraint_parameters[:equal_sign].strip()
						parameter_value = constraint_parameters[equal_sign+1:next_seperator+a]
						
						if parameter_name == 'base_property':
							self.add_property(parameter_value)
						elif parameter_name == 'class' or parameter_name == 'classes':
							self.add_classes(parameter_value)
						elif parameter_name == 'exceptions':
							self.add_exceptions(parameter_value)
						elif parameter_name == 'group by' or parameter_name == 'group property':
							self.add_group_by(parameter_value)
						elif parameter_name == 'item' or parameter_name == 'items':
							self.add_items(parameter_value)
						elif parameter_name == 'list':
							self.add_list(parameter_value, constraint_name)
						elif parameter_name == 'mandatory':
							self.add_status(parameter_value)
						elif parameter_name == 'max':
							self.add_max(parameter_value)
						elif parameter_name == 'min':
							self.add_min(parameter_value)
						elif parameter_name == 'namespace':
							self.add_namespace(parameter_value)
						elif parameter_name == 'pattern':
							self.add_pattern(parameter_value)
						elif parameter_name == 'property':
							self.add_property(parameter_value)
						elif parameter_name == 'relation':
							self.add_relation(parameter_value)
						elif parameter_name == 'value' or parameter_name == 'values':
							self.add_items(parameter_value)
						elif parameter_name == 'required' and parameter_value == 'true':
							constraint_name = 'Mandatory qualifiers'
						


						constraint_parameters = constraint_parameters[next_seperator:]
				
			
				self.write_line_in_sql_file(property_number, constraint_name)
				self.writtenLinesInInsertStatement += 1

				#prepare search for new constraint
				property_talk_page = property_talk_page[end_index:]
				start_index = property_talk_page.find(search_string)
				authority_pos = property_talk_page.find('{{Authority control properties}}') 
				normalization_pos = property_talk_page.find('== parameter value normalization ==')	
				end_of_constraint_section = authority_pos if normalization_pos < authority_pos else normalization_pos
				if start_index > end_of_constraint_section and end_of_constraint_section != -1:
					break


		if self.outputString != self.SQL_SCRIPT_HEAD:
			self.writeOutputStringToFile()


def main():
	builder = sqlScriptBuilder()
	builder.run()

if __name__ == "__main__": main()