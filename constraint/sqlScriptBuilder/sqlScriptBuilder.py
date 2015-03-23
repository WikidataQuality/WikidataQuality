from lxml import html
import requests
from collections import OrderedDict
from operator import itemgetter
import time
import codecs
import unicodedata


class sqlScriptBuilder:

	MAX_CONSTRAINT_NUMBER = 2000
	MAX_SQL_LINE_NUMBER = 1000

	parameters = {
		'class': 'NULL',
		'comment': 'NULL',
		'constraint status': 'NULL',
		'group by': 'NULL',
		'item': 'NULL',
		'known exception': 'NULL',
		'maximum date': 'NULL',
		'maximum quantity': 'NULL',
		'minimum date': 'NULL',
		'minimum quantity': 'NULL',
		'namespace': 'NULL',
		'pattern': 'NULL',
		'property': 'NULL',
		'relation': 'NULL',
		'snak': 'NULL'
	}

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
		return values.replace("{", "").replace("}", "").replace("|", "").replace(" ", "").replace("[", "").replace("]", "")

	def add_property(self, values):
		self.parameters['property'] = values

	def add_classes(self, values):
		self.parameters['class'] = self.to_comma_seperated_string(values)

	def add_exceptions(self, values):
		self.parameters['known exception'] = self.to_comma_seperated_string(values)

	def add_group_by(self, values):
		self.parameters['group by'] = values

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
		if constraint_name == "Qualifiers":
			self.parameters['property'] = self.to_comma_seperated_string(values)
		else:
			self.parameter_list = self.to_comma_seperated_string(values)

	def add_status(self, values):
		self.parameters['constraint status'] = 'mandatory'

	def add_max(self, values):
		#if "0000" in values or "." in values:
		#	self.parameters['maximum date'] = values
		#else:
		self.parameters['maximum quantity'] = values

	def add_min(self, values):
		#if "0000" in values or "." in values:
		#	self.parameters['minimum date'] = values
		#else:
		self.parameters['minimum quantity'] = values

	def add_namespace(self, values):
		self.parameters['namespace'] = values

	def add_pattern(self, values):
		self.parameters['pattern'] = values.replace("\\","\\\\")

	def add_property(self, values):
		self.parameters['property'] = values

	def add_relation(self, values):
		self.parameters['relation'] = values	

	def write_line_in_sql_file(self, property_number, constraint_name):
		if self.parameter_list != 'NULL':
			for line in self.parameter_list.split(';'):
				self.outputString += ( '(' + format(property_number) + ', \"' + constraint_name.strip() + '\"' )
				if ':' in line:
					self.parameters['class'] = line[line.index(':')+1:]
					self.parameters['property'] = line[:line.index(':')-1]
				else:
					self.parameters['property'] = line
				for par in sorted(self.parameters):
					if self.parameters[par] == 'NULL':
						self.outputString += (", NULL")
					else:
						self.outputString += (", \"" + self.parameters[par] + "\"")
				self.parameters['class'] = 'NULL'
				self.parameters['property'] = 'NULL'
				self.outputString += ("),\n")
			for par in self.parameters:
				self.parameters[par] = 'NULL'
			self.parameter_list = 'NULL'
		else:
			self.outputString += ( '(' + format(property_number) + ', \"' + constraint_name.strip() + '\"' )
			for par in sorted(self.parameters):
				if self.parameters[par] == 'NULL':
					self.outputString += (", NULL")
				else:
					self.outputString += (", \"" + self.parameters[par] + "\"")
				self.parameters[par] = 'NULL'
			self.outputString += ("),\n")

	# only purpose: Build SQL-Statement to fill table with constraints
	# fetches constraints from property talk pages
	# nonetheless: use table layout that will suit the new way of storing 
	# constraints as statements on properties

	def run(self):
		# File for SQL statements for constraints and their parameters
		writtenLinesInSqlFile = 0
		sqlFileCounter = 1
		# print("writing into constraints_table" + str(sqlFileCounter) + ".sql")
		# sql_file = codecs.open("constraints_table" + str(sqlFileCounter) + ".sql", "w", "utf-8")
		self.outputString = ("INSERT INTO constraints_ready_for_migration (pid, constraint_name, class, comment, constraint_status, group_by, item, known_exception, maximum_date, maximum_quantity, minimum_date, minimum_quantity, namespace, pattern, property, relation, snak) VALUES\n")

		#this is how every constraint template begins
		search_string = "{{Constraint:"

		# for property_number in range(1, self.MAX_CONSTRAINT_NUMBER+1):
		for property_number in range(1, 25):

			if (writtenLinesInSqlFile > self.MAX_SQL_LINE_NUMBER):
				print("writing into constraints_table" + str(sqlFileCounter) + ".sql")
				with codecs.open("constraints_table" + str(sqlFileCounter) + ".sql", "w", "utf-8") as sql_file:
					sql_file.write(self.outputString.rstrip(",\n"))

				sqlFileCounter = sqlFileCounter + 1
				writtenLinesInSqlFile = 0
				self.outputString = ("INSERT INTO constraints_ready_for_migration (pid, constraint_name, class, comment, constraint_status, group_by, item, known_exception, maximum_date, maximum_quantity, minimum_date, minimum_quantity, namespace, pattern, property, relation, snak) VALUES\n")



			#self.outputString += (30*"=" + "Property " + format(property_number) + 30*"=")
			if property_number % 10 == 0:
				print(format(property_number) + "/" + format(self.MAX_CONSTRAINT_NUMBER))
			property_talk_page = requests.get("http://www.wikidata.org/w/index.php?title=Property_talk:P" + str(property_number) + "&action=edit").text
			
			# check if property exists
			if property_talk_page.find("Creating Property talk") != -1:
				continue;

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
				self.parameter_list = 'NULL'

				delimiter_index = constraint_string.find('|')

				if delimiter_index == -1:
					constraint_name = constraint_string
				else:			
					constraint_name = constraint_string[:delimiter_index]
					constraint_parameters = constraint_string[delimiter_index+1:]

					#delete <nowiki> </nowiki> tags from parameters 
					constraint_parameters = constraint_parameters.replace("&lt;nowiki>","").replace("&lt;/nowiki>","").replace("&amp;lt;nowiki&amp;lt;","").replace("&amp;lt;/nowiki&amp;gt;","")
					
					#delete <!-- --> comments from parameters
					open_index = constraint_parameters.find("&lt;!--")
					while (open_index) != -1:
						close_index = constraint_parameters.find("-->")
						constraint_parameters = constraint_parameters[:open_index] + constraint_parameters[close_index+3:]
						open_index = constraint_parameters.find("&lt;!--")

					while constraint_parameters != None and constraint_parameters.find('=') != -1:
						equal_sign = constraint_parameters.find('=')
						next_seperator = self.find_next_seperator(constraint_parameters, equal_sign)
						a = 0 if next_seperator == -1 else -1
						parameter_name = constraint_parameters[:equal_sign].strip()
						parameter_value = constraint_parameters[equal_sign+1:next_seperator+a]
						
						if parameter_name == 'base_property':
							add_property(parameter_value)
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
						# TODO: value --> class? 
						


						constraint_parameters = constraint_parameters[next_seperator:]
				
			
				self.write_line_in_sql_file(property_number, constraint_name)
				writtenLinesInSqlFile = writtenLinesInSqlFile + 1

				#prepare search for new constraint
				property_talk_page = property_talk_page[end_index:]
				start_index = property_talk_page.find(search_string)
				authority_pos = property_talk_page.find('{{Authority control properties}}') 
				normalization_pos = property_talk_page.find('== parameter value normalization ==')	
				end_of_constraint_section = authority_pos if normalization_pos < authority_pos else normalization_pos
				if start_index > end_of_constraint_section and end_of_constraint_section != -1:
					break

		print("writing into constraints_table" + str(sqlFileCounter) + ".sql")
		with codecs.open("constraints_table" + str(sqlFileCounter) + ".sql", "w", "utf-8") as sql_file:
			sql_file.write(self.outputString.rstrip(",\n"))


def main():
	builder = sqlScriptBuilder()
	builder.run()

if __name__ == "__main__": main()