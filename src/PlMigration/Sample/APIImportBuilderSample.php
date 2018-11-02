<?php

use PlMigration\Builder\FtpBuilder;
use PlMigration\Builder\ApiImportBuilder;

require __DIR__.'/../../../vendor/autoload.php';

$ftpBuilder = new FtpBuilder();
$ftp = $ftpBuilder->host('ftp.server.com')
    ->username('username')
    ->password('password')
    ->port('port')
    ->build();

$importer = new ApiImportBuilder();

$importer
    //set the input file
    ->inputFile('/path/to/file', null)
    ->delimiter(',')
    ->enclosure('')
    ->errorLog('/path/to/errorlog/file')
    ->transactionLog('/dir/to/transactionlog')
    //setup API connection configuration
    ->host('https://domain.com')
    ->clientId('client_id')
    ->clientSecret('client_secret')
    ->username('username')
    ->password('password')
    ->primaryOrgId('primary org id')
    //setup service
    ->serviceEndpoint('/service/path'); //Set the service to use

//There are two ways to connect the data in the file to the api

//When using the addField() function, first field in the data will be mapped the selected api field from the first call
//The second field in the data file will be mapped to the api field specified in the second call. ORDER IS VITAL.
// example:
// data file contents:
// testlogmein,smith,denver,qwerty
$importer
    ->addField('member') //testlogmein will be set to the member api field
    ->addField('last_name') //smith will be set to the last_name field
    ->addField('location') //denver will be set to the location api field
    ->addField('password') //qwerty will be set to the password api field
    ->import();

//OR
//When using the mapField() function, multiple api fields can be mapped to the same field in the data file.
//The column number will start from 0. The order does not matter, but choosing the correct map column is hard.
//CORRECT MAPPING IS VITAL.
// example:
// data file contents:
// testlogmein,smith,denver,qwerty
$importer
    ->mapField('first_name', 0) //first_name field will read the first column value (testlogmein)
    ->mapField('last_name', 1) //last_name field will read the second column value (smith)
    ->mapField('member', 0) //member field will ALSO read the first column value (testlogmein)
    ->mapField('password', 3) //password field will read the fourth column value (qwerty)
    ->mapField('location', 2) //location field will read the third column value (denver)
    ->import();