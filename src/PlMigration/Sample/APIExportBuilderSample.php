<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/8/18
 */

use PlMigration\Builder\APIExportBuilder;
use PlMigration\Builder\TimeFormat;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Exceptions\ExportException;
use PlMigration\Model\Field;

require __DIR__.'/../../../vendor/autoload.php';

$builder = new APIExportBuilder();

try
{
    $builder
        //Set output file settings
        ->outputFilename('output.csv')
        ->enclosure('"')
        ->delimiter(',')
        //Set API configurations
        ->host('https://localhost-services.playerlync.com')
        ->clientId('41f88b60-2e16-11e5-a049-0ad2ffa299ae')
        ->clientSecret('41f88bbc-2e16-11e5-a049-0ad2ffa299ae')
        ->username('localhostadmin')
        ->password('ef21ad83-2e15-11e5-a049-0ad2ffa299ae')
        ->serviceEndpoint('/members') //service path to get information from starting after the api version (v3/members, v3/
        ->filter('delete_date|isnull')
        ->orderBy('member')
        ->timeFormat(TimeFormat::YEAR,'/',TimeFormat::MONTH,'/',TimeFormat::DAY,' ',
            TimeFormat::HOUR_12,':',TimeFormat::MINUTES,' ',TimeFormat::MERIDIAN) //Creates the following date format (2018/10/02 03:17 PM)
        //Set protocol to use to transfer the file to another location
        ->ftpConnect('localhost', 'miguel', 'miguel', 25)
        //Select final destination directory.
        ->sendTo('/test folder')
        //Enabled header row to be written in output file
        ->includeHeaders()
        //Add fields to be filled into the output file
        ->addField('member', 'username')
        ->addField('first_name') //The header name and api_field
        ->addField('last_name')
        ->addField(null, 'Salary') //The field will not be filled by anything.
        ->addField('create_date', 'StartDate')
        ->addField('constant', 'Header', Field::CONSTANT) //All records in this field will hold the value 'constant'
        ->build()
        ->export();
}
catch (BuilderException $e)
{
    echo 'ERROR:'.$e->getMessage();
}
catch(ExportException $e)
{
    echo 'export Failed:'.$e->getMessage();
}
exit(0);