import gtfs_realtime_pb2
import sys


if len(sys.argv) != 2:
    print("Usage:", sys.argv[0], "<protobuf file>")
    sys.exit(-1)

updates = gtfs_realtime_pb2.FeedMessage()

# Read the existing address book.
with open(sys.argv[1], "rb") as f:
    updates.ParseFromString(f.read())
    f.close()

relevantTrips = ["2512376","2512377","2512375"]


print(updates)

# for entity in updates.entity:
#     if entity.trip_update == "":
#         continue

#     printedEntity = False

#     print(entity)

#     # for update in entity.trip_update.stop_time_update:
#     #     if update.stop_id in relevantTrips:
#     #         print(update)

#         # if update.stop_id in relevantTrips:
#         #     print(update)
#         # else:
#         #     print(update.stop_id + " is not :(")


