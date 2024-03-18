import gtfs_realtime_pb2
import sys


if len(sys.argv) != 2:
    print("Usage:", sys.argv[0], "ADDRESS_BOOK_FILE")
    sys.exit(-1)

updates = gtfs_realtime_pb2.FeedMessage()

# Read the existing address book.
with open(sys.argv[1], "rb") as f:
    updates.ParseFromString(f.read())
    print (updates)