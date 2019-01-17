<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/17/18
 */

namespace PlMigration\Builder;

use PlMigration\Client\FtpClient;

/**
 * Builder to create an ftp connection object
 *
 * @package PlMigration\Builder
 */
class FtpBuilder
{
    /**
     * Hostname url
     * @var string
     */
    private $host;

    /**
     * username for connection
     * @var string
     */
    private $username;

    /**
     * password for connection
     * @var string
     */
    private $password;

    /**
     * Port number. Default value 21
     * @var int
     */
    private $port = 21;

    /**
     * Set the hostname
     * @param $host
     * @return FtpBuilder
     */
    public function host($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Set the username of the ftp server
     * @param $username
     * @return FtpBuilder
     */
    public function username($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Set the password of the ftp server
     * @param $password
     * @return FtpBuilder
     */
    public function password($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Set the port of the ftp server
     * @param $port
     * @return FtpBuilder
     */
    public function port($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * Build the ftp client
     * @return FtpClient
     */
    public function build()
    {
        return new FtpClient($this->host, $this->username, $this->password, $this->port);
    }
}