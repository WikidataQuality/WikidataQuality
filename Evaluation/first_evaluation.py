import requests
from collections import OrderedDict
import codecs
import os

class sqlScriptBuilder:

	FILE_NAME = "test.txt"

	month_string_to_number = {
		'January' : 1,
		'February' : 2,
		'March' : 3,
		'April' : 4,
		'May' : 5,
		'June' : 6,
		'July' : 7,
		'August' : 8,
		'September' : 9,
		'October' : 10,
		'November' : 11,
		'December' : 12
	}

	def cut_off_what_is_no_edit(self, response):
		search_string = '<ul id=\"pagehistory\">'
		start = response.find(search_string)
		end = response.find("</ul>", start)
		return response[start+len(search_string)+1:end]

	def get_year(self, entry):
		return int(entry[-4:])
		
	def get_month(self, entry):
		first_whitespace = entry.find(' ')
		begin = entry.find(' ', first_whitespace+1) + 1
		end = entry.find(' ', begin)
		month = entry[begin:end]
		return int(self.month_string_to_number[month])

	def get_day(self, entry):
		return int(entry[7:9].strip())

	def get_hour(self, entry):
		if entry[0] == 0:
			return int(entry[1])
		else:
			return int(entry[:1]) 

	def get_minute(self, entry):
		if entry[3] == 0:
			return int(entry[4])
		else:
			return int(entry[3:4])


	def cut_off_what_is_no_date(self, entry):
		search_string = 'class=\"mw-changeslist-date\">'
		begin = entry.find(search_string) + len(search_string)
		end = entry.find('</a>', begin)
		return entry[begin:end]
		
	def parse_date(self, entry):
		entry = self.cut_off_what_is_no_date(entry)
		date = {}
		date['year'] = self.get_year(entry)
		date['month'] = self.get_month(entry)
		date['day'] = self.get_day(entry)
		date['hour'] = self.get_hour(entry)
		date['minute'] = self.get_minute(entry)
		return date

	def parse_date_from_timestamp(self, timestamp):
		date = {}
		date['year'] = int(timestamp[0:4])
		date['month'] = int(timestamp[4:6])
		date['day'] = int(timestamp[6:8])
		date['hour'] = int(timestamp[8:10])
		date['minute'] = int(timestamp[10:12])
		return date

	def is_in_time(self, entry, date):
		date_of_entry = self.parse_date(entry)
		if date_of_entry['year'] > date['year']:
			return True
		elif date_of_entry['year'] == date['year']: 
			if date_of_entry['month'] > date['month']:
				return True
			elif date_of_entry['month'] == date['month']:
				if date_of_entry['day'] > date['day']:
					return True
				elif date_of_entry['day'] == date['day']:
					if date_of_entry['hour'] > date['hour']:
						return True
					elif date_of_entry['hour'] == date['hour']:
						if date_of_entry['minute'] >= date['minute']:
							return True
		else:
			return False

	def is_valid_entry(self, entry):
		return entry.find("<li><span class=\"mw-history-histlinks\">") != -1

	def is_valid_entry_in_time(self, entry, date):
		if self.is_valid_entry(entry) and self.is_in_time(entry, date):
			return True
		
		return False

	def get_author(self, entry):
		search_string = 'title=\"User:'
		begin = entry.find(search_string) + len(search_string)
		end = entry.find('\"', begin)
		return entry[begin:end]

	def get_amount_of_edits_since(self, date, response):
		response = self.cut_off_what_is_no_edit(response)
		response = response.split("\n")
		author_with_edits = {}
		for line in response:
			author = self.get_author(line)
			if self.is_valid_entry_in_time(line, date):
				author_with_edits[author] = author_with_edits.get(author, 0) + 1
			else:
				break
			
		return author_with_edits


	def run(self):
#		# delete old file
#		if os.path.exists(self.FILE_NAME):
#			os.remove(self.FILE_NAME)

#		test = codecs.open(self.FILE_NAME, "a", "utf-8")
#		test.write(response)

		special_page_views = codecs.open("special_page_views.csv", "r", "utf-8")
		
		for line in special_page_views:
			single_view = line.split(';')
			date_of_visit_of_special_page = self.parse_date_from_timestamp(single_view[1])
			entity = single_view[0]
			response = requests.get("http://www.wikidata.org/w/index.php?title=" + entity + "&offset=&limit=20&action=history").text
			edits = self.get_amount_of_edits_since(date_of_visit_of_special_page, response)
			print(entity, edits)
			print()
			

def main():
	builder = sqlScriptBuilder()
	builder.run()

if __name__ == "__main__": main()