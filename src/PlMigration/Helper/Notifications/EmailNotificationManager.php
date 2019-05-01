<?php


namespace PlMigration\Helper\Notifications;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PlMigration\Exceptions\NotificationException;
use PlMigration\Helper\LoggerTrait;

/**
 * Class EmailNotification
 * @package PlMigration\Helper\Notifications
 */
class EmailNotificationManager implements INotificationManager
{
    use LoggerTrait;
    /**
     * Array of requests that will be sent with the send function
     * @var EmailRequest[]
     */
    protected $requests;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * EmailNotification constructor.
     * @param $host
     * @param $port
     * @param $username
     * @param $password
     * @param array $config
     */
    public function __construct($host, $port, $username, $password, $config = [])
    {
        $this->host = $host;  // SMTP server hostname
        $this->port = $port;  // SMTP port mail to be sent on
        $this->username = $username;     // SMTP account username
        $this->password = $password;     // SMTP account password

        if(isset($config['logger']))
        {
            $this->setLogger($config['logger']);
        }
    }

    /**
     * @param INotificationRequest $request
     * @return mixed|void
     */
    public function addRequest(INotificationRequest $request)
    {
        if($request instanceof EmailRequest)
        {
            $this->requests[] = $request;
        }
    }

    /**
     * @param bool $throwException
     * @throws NotificationException
     */
    public function send($throwException = false)
    {
        foreach($this->requests as $request)
        {
            $mailer = $this->setupMailer();
            $mailer->Body = $request->body;
            $mailer->Subject = $request->subject;
            if(!empty($request->replyTo))
            {
                if(!$mailer->addReplyTo($request->replyTo))
                {
                    $this->warning($mailer->ErrorInfo);
                }
            }
            foreach($request->getBcc() as $bcc) {
                $this->addBCC($mailer, $bcc);
            }
            foreach($request->getCc() as $cc) {
                $this->addCC($mailer, $cc);
            }
            foreach($request->getRecipients() as $recipient) {
                $this->addRecipient($mailer, $recipient);
            }
            foreach($request->getAttachments() as $attachment) {
                $this->addAttachment($mailer,$attachment);
            }

            $this->setFrom($mailer, $request->from, $request->fromName);
            if(!$mailer->send())
            {
                $this->error($mailer->ErrorInfo);
                if($throwException)
                    throw new NotificationException($mailer->ErrorInfo);
                continue;
            }
            $this->debug('Sent email. Subject: '.$request->subject);
            $this->debug('Recipients: '. implode(',',$request->getRecipients()));
        }
        $this->requests = [];
    }

    /**
     * @param PHPMailer $mailer
     * @param $attachment
     */
    protected function addAttachment($mailer, $attachment)
    {
        if(!$mailer->addAttachment($attachment))
        {
            $this->warning($mailer->ErrorInfo);
        }
    }

    /**
     * @param PHPMailer $mailer
     * @param $address
     */
    protected function addBCC($mailer, $address)
    {
        if(!$mailer->addBCC($address))
        {
            $this->warning($mailer->ErrorInfo);
        }
    }

    /**
     * @param PHPMailer $mailer
     * @param $address
     */
    protected function addRecipient($mailer, $address)
    {
        if(!$mailer->addAddress($address))
        {
            $this->warning($mailer->ErrorInfo);
        }
    }

    /**
     * @param PHPMailer $mailer
     * @param $address
     */
    protected function addCC($mailer, $address)
    {
        if(!$mailer->addCC($address))
        {
            $this->warning($mailer->ErrorInfo);
        }
    }

    /**
     * @param PHPMailer $mailer
     * @param $fromEmail
     * @param $fromName
     * @return bool
     */
    protected function setFrom($mailer, $fromEmail, $fromName)
    {
        $fromName = empty($fromName) ? 'Playerlync' : $fromName;
        if(!$mailer->setFrom($fromEmail, $fromName))
        {
            $this->error($mailer->ErrorInfo);
            return false;
        }
        return true;
    }

    /**
     * @return PHPMailer
     */
    protected function setupMailer(): PHPMailer
    {
        $mailer = new PHPMailer();
        $mailer->CharSet = 'UTF-8';
        $mailer->Mailer = 'smtp';
        $mailer->SMTPDebug = 0; // enables SMTP debug information (for testing)
        $mailer->SMTPAuth = true;
        $mailer->isHTML(false);
        $mailer->Host = $this->host;
        $mailer->Port = $this->port;
        $mailer->Username = $this->username;
        $mailer->Password = $this->password;
        return $mailer;
    }
}