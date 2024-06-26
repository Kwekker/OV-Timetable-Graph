#include <iostream>
#include <fstream>
#include <sstream>
#include <string>
#include <cstdio>
#include <cstring>
#include <cstdint>
#include <map>
#include <vector>
#include <set>

// Compile with:
// g++ convertSchedule.cpp -Wall -o convert


typedef uint32_t secondsTime_t;

struct TripInfo {
    uint32_t departure;
    uint32_t arrival;
    std::string tripID;
};

bool operator<(const TripInfo first, const TripInfo second) {
    // Make sure to sort the trips by departure.
    return first.departure < second.departure;
}

void stringPad(char *dest, std::string from, uint8_t length);
secondsTime_t timeToSeconds(std::string in);


int main(int argc, const char** argv) {

    if(argc < 3) {
        std::cout << "Usage: ./convert <path to stop_times.txt> <output file>" << std::endl;
        return -1;
    }

    std::ifstream stopTimes(argv[1]);
    std::ofstream outStream(argv[2], std::ios::binary);

    std::map<std::string, std::set<TripInfo>> stops;

    std::string line; 
    getline(stopTimes, line);

    uint8_t maxStopIDLength = 0;
    uint8_t maxTripIDLength = 0;
    while (getline(stopTimes, line)) {

        // todo: magic numbers
        std::string columns[6];
        std::istringstream stringStream(line);
        for (uint8_t i = 0; i < 6; i++) {
            getline(stringStream, columns[i], ',');
        }
        
        stops[columns[2]].insert(
            // Departure, arrival, trip ID.
            {timeToSeconds(columns[5]), timeToSeconds(columns[4]), columns[0], }
        );

        if(columns[2].length() > maxStopIDLength) maxStopIDLength = columns[2].length();
        if(columns[0].length() > maxTripIDLength) maxTripIDLength = columns[0].length();
        
    }
    stopTimes.close();


    // Create a dummy stop that is placed at the end of all other stops (I hope)
    std::string dummyStopID(maxStopIDLength, 'z');
    stops[std::string(maxStopIDLength, 'z')].insert({0, 0, "kwek"});

    // Start generating the file.
    // File is:
    //   8 bytes of index length in bytes
    //   1 byte of maximum stop ID length
    //   1 byte of maximum trip ID length
    //   Index (array):
    //      stopID: MAX_STOP_ID_LENGTH bytes
    //      filePtr: value for fseek to go to in the file to find the trip array for this stop

    const uint64_t stopEntryLength = maxStopIDLength + sizeof(uint64_t);
    const uint64_t indexLength = stops.size() * stopEntryLength;
    const uint64_t headerLength = sizeof(indexLength) + sizeof(maxStopIDLength) + sizeof(maxTripIDLength);
    const uint64_t tripEntryLength = maxTripIDLength + 2 * sizeof(secondsTime_t);

    outStream.write((char*) (&indexLength), sizeof(indexLength));
    outStream.write((char*) (&maxStopIDLength), sizeof(maxStopIDLength));
    outStream.write((char*) (&maxTripIDLength), sizeof(maxTripIDLength));

    std::cerr << "index, maxStopID and maxTripID length: "
        << indexLength << ", " << (int)maxStopIDLength << ", " << (int)maxTripIDLength << std::endl;

    // Make the index
    uint64_t tripEntryFilePos = indexLength + headerLength;
    for (auto iter = stops.begin(); iter != stops.end(); iter++) {
        // The stopID string needs to be a constant length for the whole binary search thing to work.
        char stopIDChar[maxStopIDLength] = {0};
        stringPad(stopIDChar, iter->first, maxStopIDLength);

        outStream.write(stopIDChar, maxStopIDLength);
        outStream.write((char*)(&tripEntryFilePos), sizeof(tripEntryFilePos));

        tripEntryFilePos += iter->second.size() * tripEntryLength;
    }

    // Make the contents
    for (auto stopIter = stops.begin(); stopIter != stops.end(); stopIter++) {

        for (auto tripIter = stopIter->second.begin(); tripIter != stopIter->second.end(); tripIter++) {
            // Same story for the trip ID. I'm storing these as strings because they're given as strings.
            // I don't think it's specified that IDs need to be numbers and I don't want to find out by my program crashing.
            char tripIDChar[maxTripIDLength] = {0};
            stringPad(tripIDChar, tripIter->tripID, maxTripIDLength);

            outStream.write((char*)(&tripIter->departure), sizeof(secondsTime_t));
            outStream.write((char*)(&tripIter->arrival), sizeof(secondsTime_t));
            outStream.write(tripIDChar, maxTripIDLength);
        }
    }

    outStream.close();

    return 0;
}

// Pad a string to a certain length and throw it into a char array because they are better than std::string.
// I kinda don't like C++ :(
void stringPad(char *dest, std::string from, uint8_t length) {
    strncpy(dest, from.c_str(), length);
    if(from.length() < length) 
        memset(dest + from.length(), '\0', length - from.length());
}


secondsTime_t timeToSeconds(std::string in) {
    int hours, mins, secs;
    sscanf(in.c_str(), "%d:%d:%d", &hours, &mins, &secs);

    return hours * 60 * 60 + mins * 60 + secs;
}