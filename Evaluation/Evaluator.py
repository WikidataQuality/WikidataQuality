import datetime
import csv
import json
from DateParser import DateParser


class Evaluator(object):

    # time in minutes after that an edit on the item page is not counted anymore when calculating how many changes occured after an view on a edit page
    intervall = 10

    def __init__(self, log):
        super(Evaluator, self).__init__()
        self.log_file_path = log
        self.date_parser = DateParser()

    # writes to a csv file every change thar occured during a session
    # the script takes the info it needs from the log file passed in its constructor

    # might also write another file (or in the same file) which states wheather a change occured on the items page during a intervall (see self.intervall)


    def _summarize_summary(self, result):
        summary = {'violation': 0, 'compliance': 0, 'exception': 0}
        for res in result:
            summary['violation'] += result[res]['violation']
            summary['compliance'] += result[res]['compliance']
            summary['exception'] += result[res]['exception']

        return summary

    def _find_index_of_latest_visit_of_session(self, lines, special_page, entity_id, start_time, start_index):


        # TODO: return latest index...
        return 0

    def _delete_unneeded_entries_for_session(self, lines, special_page, entity_id, i, end_index):
        # TODO: delete...
        return lines

    def run(self):
        csv_file = open( "csv/evaluation" + datetime.datetime.now().strftime("%Y-%m-%d-%H-%M-%S") + ".csv", "wb")
        csv_writer = csv.writer(csv_file)
        lines = [line.strip() for line in open(self.log_file_path)]

        i = 0;
        while i < len(lines):
            if lines[i].find("unittest") != -1:
                continue
            log_entry = json.loads(lines[i][lines[i].find("{"):])
            special_page = log_entry["special_page_id"]
            entity_id = log_entry["entity_id"]
            start = self.date_parser.get_date_from_timestamp(log_entry["insertion_timestamp"])
            end_index = self.find_index_of_latest_visit_of_session(lines, special_page, entity_id, start, i)
            lines = self.delete_unneeded_entries_for_session(lines, special_page, entity_id, i, end_index)
            result_summary = self.summarize_summary(log_entry["result_summary"])
            csv_writer.writerow((special_page, entity_id, start, result_summary['violation'], result_summary['compliance'], result_summary['exception']))


        # repeat until list is empty
        # 	take first entry, memorize result, SPid and entityId and search last visit entry that belongs to session
        # 	delete every entry with this SPid and entityId until last, search all belonging job entries, memorize results, delete them and every job entry before
        # 	build Session object, write this entry to csv file

        csv_file.close()

def main():
    evaluator = Evaluator("sampleLog.log")
    evaluator.run()

if __name__ == "__main__": main()