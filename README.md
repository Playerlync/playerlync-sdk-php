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
