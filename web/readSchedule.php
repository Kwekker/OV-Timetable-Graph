<?php

$stopTrips = fopen("stop_trips.dat", "r");
$sizes = unpack("P1index/C1stopID/C1tripID", fread($stopTrips, 10));

$someStation = "2496184";
$fileIndex = getFileIndexFromIndex($stopTrips, $someStation, $sizes["index"], $sizes["stopID"]);

echo "index is $fileIndex\n";

fseek($stopTrips, $fileIndex);
$tripID = fread($stopTrips, $sizes["tripID"]);
$rest = unpack("V1arrival/V1departure", fread($stopTrips, 8));

echo "heya $tripID and ";
var_dump($rest);

fclose($stopTrips);

// File is:
//   4 bytes of index length in bytes
//   1 byte of maximum stop ID length
//   1 byte of maximum trip ID length
//   Index (array):
//      stopID: MAX_STOP_ID_LENGTH bytes
//      filePtr: value for fseek to go to in the file to find the trip array for this stop

// Index is referring to the block of data at the beginning of stop_trips.dat
function getFileIndexFromIndex($file, $stopID, $indexSize, $maxStopIDSize) {
    $indexEntrySize = 8 + $maxStopIDSize;
    $indexEntries = $indexSize / $indexEntrySize;
    $low = 0;
    $high = $indexEntries;
    $current = 0;

    if ($indexEntries != floor($indexEntries)) {
        echo "Something weierd happenin!!\n";
        return;
    }

    // echo "haha: " . fread($file, $maxStopIDSize) . "\n";
    // fseek($file, -$maxStopIDSize, SEEK_CUR);
    // fseek($file, 25 * $indexEntrySize, SEEK_CUR);
    // echo "HEHE: " . fread($file, $maxStopIDSize) . "\n";
    // die;

    // Prepare for compensation mentioned in next comment.
    while ($low <= $high) {
        $mid = floor(($high + $low) / 2);

        // The - $maxStopIDSize is to compensate for reading the stop ID in the previous iteration.
        $relative = ($mid - $current) * $indexEntrySize;
        fseek($file, $relative, SEEK_CUR);
        $current = $mid;


        $thisStopID = fread($file, $maxStopIDSize);

        if($thisStopID == $stopID) 
            return unpack("P1ret", fread($file, 8))["ret"]; 

        if($stopID < $thisStopID) {
            $high = $mid - 1;
        }
        else {
            $low = $mid + 1;
        }

        fseek($file, -$maxStopIDSize, SEEK_CUR);
    }

    return false;
}

?>