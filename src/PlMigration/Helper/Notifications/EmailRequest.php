<?php


namespace PlMigration\Helper\Notifications;

/**
 * Class that handles the content of an email request to send out
 * Class EmailRequest
 * @package PlMigration\Helper\Notifications
 */
class EmailRequest implements INotificationRequest, Attachable
{
    /**
     * Email body
     * @var string
     */
    public $body = '';

    /**
     * Subject line of the email
     * @var string
     */
    public $subject = '';

    /**
     * List of recipients of the email
     * @var array
     */
    protected $recipients = [];

    /**
     * List of BCC recipients
     * @var array
     */
    protected $bcc = [];

    /**
     * List of CC recipients
     * @var array
     */
    protected $cc = [];

    /**
     * List of file attachments for this email
     * @var array
     */
    protected $attachments = [];

    /**
     * List of attachment types allowed by this email request.
     * When the export and import script run, they will attempt to add all files created but will be filtered by this list
     * @var array
     */
    protected $attachmentsAllowed = [];

    /**
     * Email address of the sender of the email
     * @var string
     */
    public $from = '';

    /**
     * User friendly name of the sender email address
     * @var string
     */
    public $fromName = '';

    /**
     * Optional reply to email address in case the sender should not be sent replies
     * @var string
     */
    public $replyTo = '';

    /**
     * Instantiate an email request object with configuration needed to be able to sent to the EmailNotificationManager
     * EmailRequest constructor.
     * @param $from
     */
    public function __construct($from)
    {
        $this->from = $from;
    }

    /**
     * Add attachment if it is in the list of attachments allowed.
     * To force attachment, leave the $attachmentType argument null
     * @param string $file
     * @param string $attachmentType
     */
    public function addAttachment($file, $attachmentType = null)
    {
        if($attachmentType === null || in_array($attachmentType, $this->attachmentsAllowed))
        {
            $this->attachments[] = $file;
        }
    }

    public function getBcc()
    {
        return $this->bcc;
    }

    public function getCc()
    {
        return $this->cc;
    }

    public function getRecipients()
    {
        return $this->recipients;
    }

    public function addBcc($bcc)
    {
        return $this->bcc[] = $bcc;
    }

    public function addCc($cc)
    {
        return $this->cc[] = $cc;
    }

    public function addRecipient($recipient)
    {
        return $this->recipients[] = $recipient;
    }

    /**
     * Add an attachment type that can be accepted by the request.
     * Refer to the list of constants in the Attachable class that are used
     * @param $type
     */
    public function addAcceptedAttachment($type)
    {
        $this->attachmentsAllowed[] = $type;
    }
    /**
     * Get all attachments stored
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }
}