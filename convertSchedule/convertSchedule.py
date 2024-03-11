#!/bin/python3

import csv
import sys
import time


class StopInfo:

    def __init__(self, tripID, arrival, departure):
        self.tripID = tripID
        self.arrival = arrival
        self.departure = departure
        

# Convert the hh:mm:ss time format found in GNSS to seconds.
def timeToSeconds(time):
    # Idk wtf zip does either but it seems to work so idc 
    # Thanks stackoverflow guy
    ftr = [3600,60,1]
    return sum([a*b for a,b in zip(ftr, map(int,time.split(':')))])


if len(sys.argv) < 2:
    print("I'm gonna need the stop_times.txt file in the first argument pls.")
    sys.exit(-1)

fileName = sys.argv[1]

# Dictionary with stop IDs as keys and StopInfo objects as values.
stops = {}

# stop_times.txt:
# trip_id,stop_sequence,stop_id,stop_headsign,arrival_time,departure_time,...
# 0       1             2       3             4            5

with open(fileName, newline='') as csvFile:
    reader = csv.reader(csvFile)
    next(reader, None)
    i = 0
    for row in reader:
        # newInfo = StopInfo(row[0], timeToSeconds(row[4]), timeToSeconds(row[5]))
        newInfo = StopInfo(row[0], row[4], row[5])
        if row[2] not in stops:
            stops[row[2]] = [newInfo]
        else:
            stops[row[2]].append(newInfo)

        i = i + 1
        if i % 100000 == 0:
            print("Line {}".format(i))
        if i > 10:
            break

for stop in stops:
    print("Stop: {}".format(stop))
    for trip in stops[stop]:
        print(
            "\ttrip: {}, arrival: {}, departure: {}"
            .format(trip.tripID, trip.arrival, trip.departure)
        )