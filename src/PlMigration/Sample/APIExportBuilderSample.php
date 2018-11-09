<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/8/18
 */

use PlMigration\Builder\APIExportBuilder;
use PlMigration\Builder\FtpBuilder;
use PlMigration\Builder\Helper\TimeFormat;
use PlMigration\Model\Field;

require __DIR__.'/../../../vendor/autoload.php';

$ftpBuilder = new FtpBuilder();
$ftp = $ftpBuilder->host('ftp.server.com')
    ->username('username')
    ->password('password')
    ->port(21)
    ->build();

$exportBuilder = new APIExportBuilder();
$exportBuilder
    //Set output file settings
    ->outputFile('outputSample.csv')
    ->enclosure('"')
    ->delimiter(',')
    //Set API configurations
    ->host('https://domain.com')
    ->clientId('client_id')
    ->clientSecret('client_secret')
    ->username('username')
    ->password('password')
    ->serviceEndpoint('/service/path')//service path to get information from starting after the api version (/members,/groups)
    ->filter('delete_date|isnull')
    ->orderBy('order')
    //Creates the following date format (2018/10/02 03:17 PM)
    ->timeFormat(TimeFormat::YEAR, '/', TimeFormat::MONTH, '/', TimeFormat::DAY, ' ', TimeFormat::HOUR_12, ':', TimeFormat::MINUTES, ' ', TimeFormat::MERIDIAN)
    ->runHistoryFile('history.cfg') //keeps track of the history file
    ->writeFileAppend(false) //Option to use to add multiple exports into one file. On true, file will append
    ->errorLog('error.log'); //write errors into the error log in a desired location

$exportBuilder->includeHeaders() //optional inclusion of headers in output file
    ->addField('field_1', 'login')
    ->addField('field_2', 'first name')//The header name and api_field
    ->addField('field_3', 'second name')
    ->addField(null, 'Salary')//The field will not be filled by anything.
    ->addField('constant_value', 'Constant Header', Field::CONSTANT)//All records in this field will hold the value 'constant'
    ->sendTo('/test folder', $ftp) //assign remote location if desired
    ->export();

exit(0);