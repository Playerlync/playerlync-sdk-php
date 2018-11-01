<?php
/**
 * Created by PhpStorm.
 * User: dandrew
 * Date: 8/17/15
 * Time: 10:32 AM
 */

require __DIR__ . '/vendor/autoload.php';

$playerlyncServicesHost = 'https://tenant-services.yourcompany.com:33322';

try
{
    //initialize PlayerLync service, and generate OAuth token
    $playerlync = new \PlayerLync\PlayerLync([
        "host" => $playerlyncServicesHost,
        "client_id" => "replace_with_client_id",
        "client_secret" => "replace_with_client_secret",
        "username" => "replace_with_username",
        "password" => "replace_with_password"
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