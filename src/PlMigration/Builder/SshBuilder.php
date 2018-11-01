<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/30/18
 */

namespace PlMigration\Builder;

use PlMigration\Client\SftpClient;

class SshBuilder
{
    private $host;
    private $username;
    private $password;
    private $port = 22;

    /**
     * Set the hostname
     * @param $host
     * @return SshBuilder
     */
    public function host($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Set the username of the ftp server
     * @param $username
     * @return SshBuilder
     */
    public function username($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Set the password of the ftp server
     * @param $password
     * @return SshBuilder
     */
    public function password($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Set the port of the ftp server
     * @param $port
     * @return SshBuilder
     */
    public function port($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * Build the ftp client
     * @return SftpClient
     */
    public function build()
    {
        return new SftpClient($this->host, $this->username, $this->password, $this->port);
    }
}