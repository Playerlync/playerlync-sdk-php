<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/12/18
 */

namespace PlMigration\Client;

class SslFtpClient extends FtpClient
{

    public function __construct($host, $username, $password, $port = 21)
    {
        parent::__construct($host, $username, $password, $port);
        $this->ssl = true;
    }
}