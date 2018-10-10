<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/8/18
 */

use PlMigration\Builder\APIExportBuilder;
use PlMigration\Builder\TimeFormat;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Model\Field;

require __DIR__.'/../../../vendor/autoload.php';

$builder = new APIExportBuilder('output.csv', 'https://localhost-services.playerlync.com:33322');

try
{
    $export = $builder->enclosure('"')
        ->delimiter(',')
        ->clientId('41f88b60-2e16-11e5-a049-0ad2ffa299ae')
        ->clientSecret('41f88bbc-2e16-11e5-a049-0ad2ffa299ae')
        ->username('localhostadmin')
        ->password('ef21ad83-2e15-11e5-a049-0ad2ffa299ae')
        ->serviceEndpoint('/members') //service path to get information from starting after the api version (v3/members, v3/
        ->filter('delete_date|isnull')
        ->orderBy('member')
        ->includeHeaders()
        ->addField('member', 'username')
        ->addField('first_name') //The header name and api_field
        ->addField('last_name')
        ->addField(null, 'Salary') //The field will not
        ->addField('create_date', 'StartDate')
        ->addField('constant', 'Header', Field::CONSTANT) //All records in this field will hold the value 'constant'
        ->timeFormat(TimeFormat::YEAR,'/',TimeFormat::MONTH,'/',TimeFormat::DAY,' ',
            TimeFormat::HOUR_12,':',TimeFormat::MINUTES,' ',TimeFormat::MERIDIAN) //Creates the following date format (2018/10/02 03:17 PM)
        ->build();
}
catch (BuilderException $e)
{
    echo 'ERROR:'.$e->getMessage();
    exit(0);
}

$export->export();

//OR

