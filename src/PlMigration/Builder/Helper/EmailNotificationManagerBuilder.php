<?php


namespace PlMigration\Builder\Helper;


use PlMigration\Builder\Traits\ErrorLogBuilderTrait;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Helper\Notifications\EmailNotificationManager;

/**
 * Builder class to configure the creation of an email manager to send emails
 * Class EmailNotificationBuilder
 * @package PlMigration\Builder\Helper
 */
class EmailNotificationManagerBuilder
{
    use ErrorLogBuilderTrait;

    protected $config = [];
    protected $host;
    protected $port;
    protected $username;
    protected $password;

    /**
     * Set the host name of the SMTP server to use for sending emails
     * @param $host
     * @return $this
     */
    public function host($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * set the smtp username to be used to connect to the SMTP server
     * @param $username
     * @return $this
     */
    public function username($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Set the smtp password to be used to connect to the SMTP server
     * @param $password
     * @return $this
     */
    public function password($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Set the port number that is used to connect to the SMTP server
     * @param $port
     * @return $this
     */
    public function port($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * Create a new email manager object
     * @throws BuilderException
     */
    public function build()
    {
        $this->buildErrorLog('EmailLog');
        $this->config['logger'] = $this->errorLog;
        return new EmailNotificationManager($this->host, $this->port, $this->username, $this->password, $this->config);
    }
}