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
     * @throws NotificationException
     */
    public function send()
    {
        foreach($this->requests as $request)
        {
            $mailer = $this->setupMailer();
            $mailer->Body = $request->body;
            $mailer->Subject = $request->subject;
            $mailer->addReplyTo($request->replyTo);
            foreach($request->getBcc() as $bcc)
                $mailer->addBCC($bcc);
            foreach($request->getCc() as $cc)
                $mailer->addCC($cc);
            foreach($request->getRecipients() as $recipient)
                $mailer->addAddress($recipient);
            foreach($request->getAttachments() as $attachment)
                $this->addAttachment($mailer,$attachment);

            try
            {
                $mailer->setFrom($request->from);
                if(!$mailer->send())
                {
                    $this->throwException($mailer->ErrorInfo);
                }
            }
            catch (Exception $e)
            {
                $this->throwException($e->getMessage());
            }
        }
        $this->requests = [];
    }

    /**
     * @param PHPMailer $mailer
     * @param $attachment
     * @throws NotificationException
     */
    protected function addAttachment($mailer, $attachment)
    {
        try {
            $mailer->addAttachment($attachment);
        } catch (Exception $e) {
            $this->throwException($e->getMessage());
        }
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
        $mailer->FromName = 'Playerlync';
        return $mailer;
    }

    /**
     * @param $message
     * @throws NotificationException
     */
    protected function throwException($message)
    {
        $this->error($message);
        throw new NotificationException($message);
    }
}