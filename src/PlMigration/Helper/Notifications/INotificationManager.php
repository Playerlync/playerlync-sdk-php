<?php


namespace PlMigration\Helper\Notifications;

use PlMigration\Exceptions\NotificationException;

/**
 * Interface INotification
 * @package PlMigration\Helper\Notifications
 */
interface INotificationManager
{
    /**
     * Send all requests that have been queued
     * @return void
     * @throws NotificationException
     */
    public function send();

    /**
     * Add a request object to be sent in
     * @param INotificationRequest $request
     * @return mixed
     */
    public function addRequest(INotificationRequest $request);
}