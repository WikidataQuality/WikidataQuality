class Date(object):

    def __init__(self, year, month, day, hour, minute):
        super(Date, self).__init__()
        self.year = year
        self.month = month
        self.day = day
        self.hour = hour
        self.minute = minute

    def get_year(self):
        return self.year

    def get_month(self):
        return self.month

    def get_day(self):
        return self.day

    def get_hout(self):
        return self.hour

    def get_minute(self):
        return self.minute

    def compare(self, date):
        if self.year > date.get_year():
            return 1
        elif self.year == date.get_year():
            if self.month > date.get_month():
                return 1
            elif self.month == date.get_month():
                if self.day() > date.get_day():
                    return 1
                elif self.day == date.get_day():
                    if self.hour > date.get_hour():
                        return 1
                    elif self.hour == date.get_hour():
                        if self.minute >= date.get_minute():
                            return 1
                        elif self.minute == date.get_minute():
                            return 0
        else:
            return -1