<?php

require "fileReader.php";

$stopTrips = fopen("stop_trips.dat", "r");
$sizes = unpack("P1index/C1stopID/C1tripID", fread($stopTrips, 10));

$someStation = "2491553";
$indexEntrySize = $sizes["stopID"] + 8;
$fileIndex = binSearch($stopTrips, $someStation, $sizes["stopID"], $indexEntrySize, $sizes["index"] / $indexEntrySize);
$fileIndex = unpack("P", $fileIndex)[1];

echo "index is $fileIndex\n";

fseek($stopTrips, $fileIndex);
$tripID = fread($stopTrips, $sizes["tripID"]);
$rest = unpack("V1arrival/V1departure", fread($stopTrips, 8));


fclose($stopTrips);


?>