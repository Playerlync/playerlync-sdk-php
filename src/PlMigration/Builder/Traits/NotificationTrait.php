<?php


namespace PlMigration\Builder\Traits;

use PlMigration\Exceptions\NotificationException;
use PlMigration\Helper\Notifications\Attachable;
use PlMigration\Helper\Notifications\INotificationManager;
use PlMigration\Helper\Notifications\INotificationRequest;

/**
 * Trait NotificationTrait
 * @package PlMigration\Builder\Traits
 */
trait NotificationTrait
{
    /**
     * Notification manager
     * @var INotificationManager
     */
    protected $notificationManager;

    /**
     * A list of notification requests to
     * @var INotificationRequest[]
     */
    protected $notifications = [];

    /**
     * Set the notification manager to send notification requests
     * @param $manager
     * @return $this
     */
    public function notificationManager($manager)
    {
        $this->notificationManager = $manager;
        return $this;
    }

    /**
     * Add a notification request to send by the notification manager
     * @param $notification
     * @return $this
     */
    public function addNotification($notification)
    {
        $this->notifications[] = $notification;
        return $this;
    }

    /**
     * Add a file attachment to the notifications.
     * The notification object must implement the Attachable interface to be added into the notification
     * @param $file
     * @param $attachmentType
     */
    protected function addAttachment($file, $attachmentType)
    {
        foreach($this->notifications as $notification)
        {
            if($notification instanceof Attachable)
            {
                $notification->addAttachment($file, $attachmentType);
            }
        }
    }

    /**
     * Send the notifications to out to the recipients
     */
    protected function sendNotifications()
    {
        foreach($this->notifications as $notif)
        {
            $this->notificationManager->addRequest($notif);
            try {
                $this->notificationManager->send();
            } catch (NotificationException $e) {
            }
        }
    }
}