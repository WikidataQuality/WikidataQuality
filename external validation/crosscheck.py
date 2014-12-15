import argparse
from musicbrainz import MusicBrainzService


if __name__ == "__main__":
	parser = argparse.ArgumentParser(description="This programm validates the properties of a given Wikidata item by using external databases.")
	parser.add_argument("wd_item_identifier", help="The Wikidata item identifier", type=str)

	args = parser.parse_args()
	MusicBrainzService.validate(args.wd_item_identifier)