class DateComparer(object):
    def __init__(self):
        super(DateComparer, self).__init__()

    # returns 1 if date_one > date_two, 0 if they are equal, -1 if date_one < date_two
    def compare(self, date_one, date_two):
        if date_one.get_year() > date_two.get_year():
            return 1
        elif date_one.get_year() == date_two.get_year():
            if date_one.get_month() > date_two.get_month():
                return 1
            elif date_one.get_month() == date_two.get_month():
                if date_one.get_day() > date_two.get_day():
                    return 1
                elif date_one.get_day() == date_two.get_day():
                    if date_one.get_hour() > date_two.get_hour():
                        return 1
                    elif date_one.get_hour() == date_two.get_hour():
                        if date_one.get_minute() >= date_two.get_minute():
                            return 1
                        elif date_one.get_minute() == date_two.get_minute():
                            return 0
        else:
            return -1

    def is_within_minutes(self, date_one, date_two):
        # TODO
        return True
