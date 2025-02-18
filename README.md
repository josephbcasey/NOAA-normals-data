# NOAA 1991-2020 Daily Climate Normals Data
Daily temperature and precipitation data from NOAA climate normals 1991-2020 in an SQLite3 database.

The National Centers for Environmental Information (NCEI) and the National Oceanic and Atmospheric Administration (NOAA) publish climate data in various formats. 

One format is US Climate Normals 1991-2020. That data is available at https://www.ncei.noaa.gov/data/normals-daily/1991-2020/archive/us-climate-normals_1991-2020_v1.0.1_daily_multivariate_by-station_c20230404.tar.gz. The gz file is 289MB. Uncompressed and extracted, there are 15,615 files totaling 4.67 GB. Each file contains data for one station in CSV format.

Climate normals are usually based on 30-year averages. 1991-2020 is the most recent available.

This data covers USA states and territories.

The data is comprehensive, including hundreds of measurements, calculations, and statistical analyses. This repository is for an SQLite3 database containing daily temperature and precipitation. In NOAA terminology, these are "dly_tmax_normal", "dly_tmin_normal", and "ytd_prcp_normal". It also includes latitude, longitude, and elevation for each station. See `USW00094728.csv` for an example source CSV file. 

Not every station has data for every variable. 

## Database Schema
```
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
```
## Sample Queries
```
sqlite> SELECT * FROM normals WHERE station = 'USC00408065' AND date = '10-26';
USC00408065|10-26|67.8|43.5|43.37

sqlite> SELECT * FROM stations WHERE location LIKE '%LOS ANGELES%';
US1CALA0064|34.0809|-118.2729|123.4|LOS ANGELES 2.6 NW, CA US
USC00415369|28.4478|-99.0656|87.8|LOS ANGELES 4 WSW, TX US
USW00023174|33.9381|-118.3889|29.6|LOS ANGELES INTL AP, CA US
USW00093134|34.0511|-118.2353|70.1|LOS ANGELES DWTN USC CAMPUS, CA US

sqlite> SELECT * FROM stations WHERE location LIKE '%LOS ANGELES%CA US%';
US1CALA0064|34.0809|-118.2729|123.4|LOS ANGELES 2.6 NW, CA US
USW00023174|33.9381|-118.3889|29.6|LOS ANGELES INTL AP, CA US
USW00093134|34.0511|-118.2353|70.1|LOS ANGELES DWTN USC CAMPUS, CA US
```

## Notes on the database
Each station has 365 rows of data in the normals table, keyed by date. The NOAA data includes February 29th, using interpolated data from February 28th and March 1st. This database does **NOT** include February 29th.

Not every station has data for every variable, but if a variable is present, there are 365 days worth.

Some location names contain obscure abbreviations, so SELECTing by location can be tricky.

Temperatures are in Fahrenheit and elevations are in meters. 

Note that precipitation values are 'ytd' - year-to-date. So to get a daily value, subtract one ytd value from the value of the succeeding day.

## About the 'doc' directory
This is official NOAA documentation. Much of it does not apply to the filtered results in the SQLite database.

## XML and JSON
Using SQLiteStudio, free software available at https://sqlitestudio.pl/, you can export the SQL data to XML or JSON. The XML output is 1.54GB. The JSON output is 1.01GB. These may be too big to be useful. They are not included in this repository.

## Notes on NOAA_CSV_to_SQLite.php
This PHP script creates a database, then reads the NOAA CSV data and INSERTs it into the database. 

It **MUST BE EDITED** to match your environment. Namely, set variables `$data_dir` and `$database_name`.

It can be modified to select different or additional values. Change the database schema and `$possible_normals_headers`.
