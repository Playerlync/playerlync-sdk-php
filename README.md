# playerlync-sdk-php
This SDK is an open-source PHP library that makes it easy integrate your PHP application with the PlayerLync REST API

## Getting Started with the PlayerLync SDK for PHP
### Autoloading & Namespaces
The PlayerLync SDK for PHP was developed in compliance with PSR-4, meaning it relies on namespaces so that class files can be loaded automatically.

### System Requirements
- PHP 5.5 or greater

### Installing the PlayerLync SDK for PHP
There are two methods for installing the PlayerLync SDK for PHP. The recommended installation method is by using Composer. If you are unable to use Composer for your project, you can still install the SDK manually by downloading the source files and including the autoloader.

### Installing with Composer (recommended)

Composer is the recommended way to install the PlayerLync SDK for PHP. Simply add the following "require" entry to the composer.json file in the root of your project.

```json
{
  "require" : {
    "playerlync/playerlync-sdk-php" : "4000.1.*"
  }
}
```

Then run composer install from the command line, and composer will download the latest version of the SDK and put it in the /vendor/ directory.

Make sure to include the Composer autoloader at the top of your script.

```php
require_once __DIR__ . '/vendor/autoload.php';
```

### Loading the SDK without Composer

If you're not using Composer, you can download the SDK from our GitHub: [playerlync-sdk-php](https://github.com/Playerlync/playerlync-sdk-php)

Load the SDK like this:

```php
define('PLAYERLYNC_SDK_PHP_SRC_DIR', '/path/to/playerlync-sdk-php/src/PlayerLync/');
require __DIR__ . '/path/to/playerlync-sdk-php/vendor/autoload.php';
```

## Configuration and Setup

>**This assumes you have already configured PlayerLync API access for your primary organization via the PlayerLync Admin Portal. This also assumes you have a valid PlayerLync user account, which also has access to the primary organization for which API access has been configured.**

Before we can send requests to the PlayerLync API, we need to load our app configuration into the PlayerLync\PlayerLync service.

```php
$playerlync = new \PlayerLync\PlayerLync([
        'host' => '{host}',
        'client_id' => '{client-id}',
        'client_secret' => '{client-secret}',
        'username' => '{username}',
        'password' => '{password}',
    ]);
```

You'll need to replace the {host} with the host (including scheme and port) where the PlayerLync services are running (ex: https://tenantname-services.playerlync.com:33322). Replace {client-id} and {client-secret} with the values provided by your PlayerLync API access configuration, and replace {username} and {password} an account that has access to the primary organization for which the API access configuration was created.

### Authentication

The PlayerLync API relies on OAuth 2.0 for authentication. The \PlayerLync\PlayerLync service takes care of requesting and renewing OAuth tokens through helper classes within the SDK, assuming a valid configuration is provided. All requests to the PlayerLync API require an access token.

## Making Requests to the PlayerLync API

Once you have an instance of the \PlayerLync\PlayerLync service and obtained an access token, you can begin making calls to the PlayerLync API.

In this example we will send a GET request to the PlayerLync API endpoint /memebers. The /members endpoint will return an array representing a collection of members for the organization associated with the access token.

```php
try
{
  $playerlync = new \PlayerLync\PlayerLync([/*.....*/]);

  $response = $playerlync->get('/members', ['limit'=>50]);
  $members = $response->getData();

  echo 'API returned '. count($members) . ' members!';
}
catch(PlayerLync\Exceptions\PlayerLyncResponseException $e)
{
  // When PlayerLync API returns an error
  echo 'PlayerLync API returned an error: ' . $e->getMessage();
  exit;
}
catch(PlayerLync\Exceptions\PlayerLyncSDKException $e)
{
  // When PlayerLync SDK fails or some other local issue
  echo 'PlayerLync SDK returned an error: ' . $e->getMessage();
  exit;
}
```

In this next example, we will send a POST request to the PlayerLync API endpoint /files, to upload / replace an existing file in the PlayerLync content system.

```php
try
{
  $playerlync = new \PlayerLync\PlayerLync([/*.....*/]);

  //FILE UPLOAD EXAMPLE
  $existingFileId = '<fileID GUID>';
  $filePath = '/path/to/your/file.txt';

  $data = [
      'fileid' => $existingFileId,
      'sourcefile' => $playerlync->fileToUpload($filePath)
  ];

  $response = $playerlync->post('/files', $data);

  $body = $response->getDecodedBody(); //get the full decoded JSON response
  $file = $response->getData(); //get just the "data" object from the decoded JSON response
}
catch(PlayerLync\Exceptions\PlayerLyncResponseException $e)
{
  // When PlayerLync API returns an error
  echo 'PlayerLync API returned an error: ' . $e->getMessage();
  exit;
}
catch(PlayerLync\Exceptions\PlayerLyncSDKException $e)
{
  // When PlayerLync SDK fails or some other local issue
  echo 'PlayerLync SDK returned an error: ' . $e->getMessage();
  exit;
}
```

##Getting started with Migration SDK
The SDK added the ability to export playerlync data into csv formatted files, and also import external data into playerlync data.
####System Requirements
php 5.6 or greater
###Importing data into Playerlync
To import data from an outside source (file, etc) into the playerlync system, an import builder class will stream line your process and requirements.

```php
$importer = new FileImportBuilder();
```
The builder class contains all configurations needed to set the data from the service, the input file, logging, and more.
####Setting up input file
In order to use the file import, a csv format file needs to be provided.
Moreover, the file may be retrieved from a remote server Client from the supported protocols we have available [here](#Connecting-to-remote-servers)
```php
$importer
    ->inputFile('/path/to/file', null) //provide a Client object to the second parameter to grab from the file from a remote server
    ->delimiter(',')
    ->enclosure('"');
```
####Setting up API connection to Playerlync
Since records will be imported using the Playerlync API, some knowledge and familiarity with the API is recommended.
The following fields are required to be able to connect with the API successfully.
```php
    $importer
    ->host('https://domain.com')
    ->clientId('client_id')
    ->clientSecret('client_secret')
    ->username('username')
    ->password('password')
    ->primaryOrgId('primary org id')
    ->postService('/v3/member'); //Set the POST plapi service to use
```
####Setting up import data fields from a file
There are two ways to connect the data from a data file to the api.

When using the addField() function, first field in the data will be mapped the selected api field from the first call
The second field in the data file will be mapped to the api field specified in the second call. ORDER IS VITAL.

```php
// example:
// data file contents:
// testlogmein,smith,denver,qwerty
$importer
    ->addField('member') //testlogmein will be set to the member api field
    ->addField('last_name') //smith will be set to the last_name field
    ->addField('location') //denver will be set to the location api field
    ->addField('password') //qwerty will be set to the password api field
    ->import();
```
When using the mapField() function, multiple api fields can be mapped to the same field in the data file.
The column number will start from 0 for the first field. The order does not matter, but choosing the correct map column is hard.
CORRECT MAPPING IS VITAL.
```php
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
```
When the import() function is called, the import process will run with the data provided.

###Exporting data from Playerlync
To export data from the playerlync system into an external source, the export tool will be able to aid with that.
Currently, only exporting to csv format files is supported.
```php
$exporter = new FileExportBuilder();
    //Creates the following date format (2018/10/02 03:17 PM)
    ->timeFormat(TimeFormat::YEAR, '/', TimeFormat::MONTH, '/', TimeFormat::DAY, ' ', TimeFormat::HOUR_12, ':', TimeFormat::MINUTES, ' ', TimeFormat::MERIDIAN)
    ->runHistoryFile('history.cfg'); //keeps track of the history file
```
####Setting up output file
When using a file exporter, the output file  configuration must be set so the data can be sent properly to it.
```php
$exporter->outputFile('outputSample.csv')
    ->enclosure('"')
    ->delimiter(',')
    ->includeHeaders(true); //optional inclusion to add top row header names setup by the addField() methods in output file
```
####Setting up Playerlync API connection
Since records will be exported by using the Playerlync API services, some knowledge and familiarity with the playerlync API is recommended.
The following fields are required to be able to connect with the API successfully for exporting.
```php
$exporter->host('https://domain.com')
             ->clientId('client_id')
             ->clientSecret('client_secret')
             ->username('username')
             ->password('password')
             ->getService('/service/path') //GET plapi service path to get information from (/members,/groups)
             ->filter('delete_date|isnull') //OPTIONAL: filter data retrieved by using the same syntax as the plapi v3 API filter query paramerter
             ->orderBy('order'); //OPTIONAL: set the order by with the same syntax as use by the  plapi v3 orderby query parameter
```
####Setting up export data fields
When exporting data onto another location, the information is added by fields. In a file export, the order of the addField() methods
determines the order in which the fields will be sent into the file.
```php
$exporter
    ->addField('field_1', 'login')
    ->addField('field_2', 'first name')//The header name and api_field
    ->addField('field_3', 'second name')
    ->addField(null, 'Salary', Field::CONSTANT)//The field will not be filled by anything.
    ->addField('constant_value', 'Constant Header', Field::CONSTANT);//All records in this field will hold the value 'constant'
```
####Send exported file to remote server
If you want to send the created file out to a remote server location, you can send it to a remote directory with a Client object that contains the
connection information
```php
$exporter->sendTo('remote/server',$ftp);
```

###Connecting to remote servers
The export & import process also allow for functionality to connect to remote servers for retrieving or sending data files.
The following protocols are supported: FTP, SFTP.

The remote connections are created by using a builder class. 
When new functionality is added to a connection, the builder class will add new methods to configure as allowed.

FTP connection example.
```php
$ftp = (new FtpBuilder())
    ->host('ftp.server.com')
    ->username('username')
    ->password('password')
    ->port(21)
    ->build();
```
SFTP connection example.
```php
$ftp = (new SftpBuilder())
    ->host('sftp.server.com')
    ->username('username')
    ->password('password')
    ->port(22)
    ->build();
```
###TroubleShooting
For troubleshooting an import or export, there is an optional functionality to use enable logging.
```php
$importer->errorLog('import_error.log');
$exporter->errorLog('export_error.log');
```
Additionally, there is an option to output transaction files for file imports to see which records were inserted succcessfully and which failed.
The error message is also added to the failure records are the end of the row data.
```php
$importer->transactionLog('./output')
```