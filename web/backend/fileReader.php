<?php
$printNowSeconds = time() - strtotime("today");
echo "Hi ja hi de huidige tijd is $printNowSeconds\n";  
$tripsPerStop = get_trips("stop_trips.dat", ["2512376","2512377","2512375"], 3600);
foreach ($tripsPerStop as $trips) {
    foreach ($trips as $trip) {
        $trip->arrival = secondsToTimeString($trip->arrival);
        $trip->departure = secondsToTimeString($trip->departure);
    }
}
var_dump($tripsPerStop);


// Gets trip IDs and arrival/departure times from a stop_trips.dat file.
// $file is the stop_trips.dat file
// $stopIDs is an array of stop IDs
// Will return all trips that stop at $stopIDs between now and now + $interval in seconds,
// as arrays in an array keyed to the stopIDs.
// That means *all* trips, including ones that do not occur today.
function get_trips($fileName, $stopIDs, $interval) {
    
    $file = fopen($fileName, "r");
    $sizes = unpack("P1index/C1stopID/C1tripID", fread($file, 10));
    $indexEntrySize = $sizes["stopID"] + 8;     // StopID, file position.
    $tripEntrySize = $sizes["tripID"] + 4 + 4;  // TripID, arrival, departure.

    $filePositions = [];
    foreach ($stopIDs as $stopID) {
        // Get this index and the next one.
        // Last entry in the index is guaranteed to be a dummy stop with an index of the end of the file.
        $pos = bin_search($file, $stopID, $sizes["stopID"], $indexEntrySize, $sizes["index"] / $indexEntrySize);
        fseek($file, $indexEntrySize, SEEK_CUR);
        $nextPos = fread($file, 8);

        $filePositions[$stopID] = ["from" => unpack("P", $pos)[1], "to" => unpack("P", $nextPos)[1]];
        // Go back to the start of the file for the next iteration
        fseek($file, 10);
    }

    $nowSeconds = time() - strtotime("today");
    $trips = [];
    
    foreach ($stopIDs as $stopID) {
        $pos = $filePositions[$stopID];
        fseek($file, $pos["from"]);
        $tripEntries = ($pos["to"] - $pos["from"] + 1) / $tripEntrySize;
        bin_search($file, $nowSeconds, 4, $tripEntrySize, $tripEntries, false, "V");
        fseek($file, -4, SEEK_CUR);
        
        $trips[$stopID] = [];
        
        do {
            $tripEntry = unpack("V1departure/V1arrival", fread($file, 8));
            $tripEntry["tripID"] = fread($file, $sizes["tripID"]);
            array_push($trips[$stopID], (object)$tripEntry);
        } 
        while ($tripEntry["departure"] < $nowSeconds + $interval);
    }

    return $trips;
}

// Generic binary search function
// $file's file pointer must point to the start of the haystack within the file.
// The data in the file must be made up as such:
//
// nnnndddddddd (repeat $entries times)
// |^^^|^^^^^^^
// |   |<Data, always $entrySize - $needleSize bytes.
// |
// |<Needle, always $needleSize bytes.
//
// Returns the data as a string.
// Returns false if $needle wasn't found and $exact is set to true.
// File pointer will end up on the first byte of the returned data.
// If $needle wasn't found file pointer will end up on the data of the entry greater than $needle.
function bin_search($file, $needle, $needleSize, $entrySize, $entries, $exact = true, $pattern = null) {
    $low = 0;
    $high = $entries;
    $current = 0;

    // Compensate for compensation.
    fseek($file, $needleSize, SEEK_CUR);
    while ($low <= $high) {
        $mid = floor(($high + $low) / 2);

        $relative = ($mid - $current) * $entrySize;
        // The -$needleSize is compensation for reading the $needle on every iteration.
        fseek($file, $relative - $needleSize, SEEK_CUR);
        $current = $mid;


        $thisNeedle = fread($file, $needleSize);

        if($thisNeedle == $needle) {
            $ret = fread($file, $entrySize - $needleSize); 
            fseek($file, -($entrySize - $needleSize), SEEK_CUR);
            return $ret;
        }
        
        if(
            $pattern == null && $needle < $thisNeedle
            || $pattern != null && $needle < unpack($pattern, $thisNeedle)[1]
        ) {
            $high = $mid - 1;
        }
        else {
            $low = $mid + 1;
        }

    }

    // Go forward one entry to end up after the position where $needle should be.
    if($needle > $thisNeedle) // php scoping is kinda cool.
        fseek($file, $entrySize, SEEK_CUR);
    
    if($exact == false) {
        $ret = fread($file, $entrySize - $needleSize);
        fseek($file, -($entrySize - $needleSize), SEEK_CUR);
        return $ret;
    }

    return false;
}


function secondsToTimeString($seconds) {
    return sprintf("%d:%02d:%02d", floor($seconds/(60*60)), floor($seconds/60) % 60, $seconds % 60);
}

?>