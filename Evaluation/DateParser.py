from Date import Date


class DateParser(object):

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

	def __init__(self):
		super(DateParser, self).__init__()
		
	def __get_year(self, entry):
		return int(entry[-4:])
		
	def __get_month(self, entry):
		first_whitespace = entry.find(' ')
		begin = entry.find(' ', first_whitespace+1) + 1
		end = entry.find(' ', begin)
		month = entry[begin:end]
		return int(self.month_string_to_number[month])

	def __get_day(self, entry):
		return int(entry[7:9].strip())

	def __get_hour(self, entry):
		if entry[0] == 0:
			return int(entry[1])
		else:
			return int(entry[:1]) 

	def __get_minute(self, entry):
		if entry[3] == 0:
			return int(entry[4])
		else:
			return int(entry[3:4])


	def __cut_off_what_is_no_date(self, entry):
		search_string = 'class=\"mw-changeslist-date\">'
		begin = entry.find(search_string) + len(search_string)
		end = entry.find('</a>', begin)
		return entry[begin:end]

	# returns date object	
	def get_date_from_edit_page_entry(self, entry):
		entry = self.cut_off_what_is_no_date(entry)
		year = self.get_year(entry)
		month = self.get_month(entry)
		day = self.get_day(entry)
		hour = self.get_hour(entry)
		minute = self.get_minute(entry)
		return Date(year, month, day, hour, minute)

	# TODO: Why not second?
	def get_date_from_timestamp(self, timestamp):
		year = int(timestamp[0:4])
		month = int(timestamp[4:6])
		day = int(timestamp[6:8])
		hour = int(timestamp[8:10])
		minute = int(timestamp[10:12])
		return Date(year, month, day, hour, minute)
