<?php

/* This script creates an SQLite database, reads the NOAA CSV files and writes selected data to the database.
    * The CSV files are the daily normals files from 1991-2020. Note that the 1991-2020 file format is different from the 1981-2010 files.

    * To extract different values, change the database schema and $possible_normals_headers.

*/

// EDIT THIS - Location of the extracted NOAA CSV files.
$data_dir = 'Location of the 15,615 NOAA CSV files';
$data_dir = 'D:/d_climate/new_ai/NOAA_data/1991-2020/normals_daily/';

// EDIT THIS - Name of the SQLite database.
$database_name = 'your chosen database name';
$database_name = 'D:\d_climate\new_ai\NOAA_1991_github.db';


//Create the SQLite database.
$db = new SQLite3($database_name);
$sql =<<<EOF
CREATE TABLE stations (
    station varchar(10),    -- 'AQW00061705',
    latitude decimal(8,4),  -- -14.3306
    longitude decimal(8,4), -- -170.7136
    elevation decimal(10,2), -- 3.7
    location varchar(50),    -- 'PAGO PAGO WSO AP, AS AQ'
    PRIMARY KEY(station)
);

CREATE TABLE normals (
    station varchar(10),          -- 'AQW00061705',
    date varchar(5),              -- '01-01'
    dly_tmax_normal decimal(4,1), -- 87.7
    dly_tmin_normal decimal(4,1), -- 78.1
    ytd_prcp_normal decimal(6,2), -- 0.45
    PRIMARY KEY(station, date)
);
EOF;
$ret = $db->exec($sql);
    if(!$ret){
        echo $db->lastErrorMsg();
        exit;
    } else {
        echo "Table created successfully\n";
    }

// Turn off synchronous mode for faster writes.
$db->exec("PRAGMA synchronous = OFF");

$station_count = 0;

$files = array_slice(scandir($data_dir), 2);

foreach ($files as $filename) {
  $filename = $data_dir . str_replace('.csv', '', $filename);
  csv_1991_sql($filename, $db);

  $station_count++;
  if ($station_count % 1000 == 0) {
    echo "$station_count\n";
  }
  break;
}

echo "station_count: $station_count\n";

function csv_1991_sql($filename, $db) {
    // Wrap the station data in a transaction for faster writes.
    $db->exec("BEGIN TRANSACTION");

    // Get the CSV data from the file
    $csvData = file($filename . '.csv');

    // Get the headers from the CSV file
    $headers = strtolower(array_shift($csvData));
    $headers = str_replace('-', '_', $headers);
    $headers = str_getcsv($headers);

    // Stations ==============================================================================

    // Station id fields. These are always the same for all station files.
    $station_headers = array("station","latitude","longitude","elevation","location");
    $station_headers_pos = array(0,2,3,4,5);
    $header_line = str_getcsv($csvData[0]);
    $values = array();
    for($i = 0; $i < count($station_headers); $i++) {
        $values[] = $header_line[$station_headers_pos[$i]];
    }

    $columns = implode(', ', $station_headers);
    $statement = $db->prepare('INSERT INTO stations (' . $columns . ') VALUES (:station, :latitude, :longitude, :elevation, :location)');
    for($i = 0; $i < count($values); $i++) {
        $statement->bindValue(':' . $station_headers[$i], $values[$i]);
    }
    $statement->execute();

    // Normals ==============================================================================

    // We only care about certain data fields, some of which may not be present. Get their indexes.
    $possible_normals_headers = array("station","date","dly_tmax_normal","dly_tmin_normal","ytd_prcp_normal");
    $normals_headers = array();
    foreach($possible_normals_headers as $possible_normals_header) {
        if(array_search($possible_normals_header, $headers) !== false) {
            $normals_headers[] = $possible_normals_header;
        }
    }
    $indexes = array();
    foreach ($normals_headers as $normals_header) {
        $normals_indexes[] = array_search($normals_header, $headers);
    }

    $columns = implode(', ', $normals_headers);
    $values_sql_headers = array();
    foreach($normals_headers as $normals_header) {
        $values_sql_headers[] = ':' . $normals_header;
    }
    $values_sql_string = implode(', ', $values_sql_headers);
    $prepare_string = 'INSERT INTO normals (' . $columns . ') VALUES ('. $values_sql_string . ')';
    $prepared = $db->prepare($prepare_string);

    // Loop through the CSV data, adding data in the fields we care about.
    foreach ($csvData as $row) {
        if(strpos($row, ',"02-29",') !== false) {
            continue;
        }
        $row = str_getcsv($row);
        for($i = 0; $i < count($normals_indexes); $i++) {
            $header = $normals_headers[$i];
            $value = trim($row[$normals_indexes[$i]]);
            $prepared->bindValue($header, $value);
        }
        $prepared->execute();
    }

    $db->exec("END TRANSACTION");
}
