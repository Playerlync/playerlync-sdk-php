<?php
/**
 * Created by PhpStorm.
 * User: dandrew
 * Date: 8/17/15
 * Time: 10:32 AM
 */

require __DIR__ . '/vendor/autoload.php';

$playerlyncServicesHost = 'https://localhost-services.playerlync.com:33322';

try
{
    //initialize PlayerLync service, and generate OAuth token
    $playerlync = new \PlayerLync\PlayerLync([
        "host" => $playerlyncServicesHost,
        "client_id" => "34795ba5-fd75-ce5a-c70a-b3ffdafdf21f",
        "client_secret" => "dc9e35ea-e698-c426-b2b0-fccef6f84861",
        "username" => "docker",
        "password" => "asdfg",
        "default_api_version" => "v3",
        "primary_org_id" => "41f88ae3-2e16-11e5-a049-0ad2ffa299ae"
    ]);

    //GET ORGANIZATIONS EXAMPLE
    $response = $playerlync->get('/organizations', ['limit' => 50]);
    $organizations = $response->getData();

    echo 'API returned ' . count($organizations) . ' organizations!';

}
catch (PlayerLync\Exceptions\PlayerLyncResponseException $e)
{
    echo 'PlayerLync API returned an error: ' . $e->getMessage();
    exit;
}
catch (PlayerLync\Exceptions\PlayerLyncSDKException $e)
{
    echo 'PlayerLync SDK returned an error: ' . $e->getMessage();
    exit;
}