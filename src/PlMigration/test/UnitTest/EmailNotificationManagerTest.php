<?php

namespace PlMigration\test\UnitTest;

use PlMigration\Exceptions\NotificationException;
use PlMigration\Helper\Notifications\EmailNotificationManager;
use PlMigration\Helper\Notifications\EmailRequest;
use PlMigration\test\UnitTest;

class EmailNotificationManagerTest extends UnitTest
{
    protected function instance()
    {
        $email = $this->getConfigData()->email;
        return new EmailNotificationManager($email->host, $email->port, $email->username, $email->password);
    }

    protected function dummyEmail()
    {
        $email = new EmailRequest($this->getConfigData()->email->from);
        $email->subject = 'dummy title';
        $email->body = 'dummy body';
        $email->addRecipient($this->getConfigData()->email->recipient);
        return $email;
    }

    /**
     * @test
     */
    public function sendEmail()
    {
        $manager = $this->instance();
        $manager->addRequest($this->dummyEmail());
        $manager->send();
    }

    /**
     * @test
     * @expectedException \PlMigration\Exceptions\NotificationException
     */
    public function sendBadEmail()
    {
        $manager = $this->instance();
        $mail = $this->dummyEmail();
        $mail->from = 'invalidEmail';
        $manager->addRequest($mail);
        $manager->send(true);
    }

    /**
     * @test
     */
    public function sendEmailWithAttachment()
    {
        $manager = $this->instance();
        $mail = $this->dummyEmail();
        $mail->addAttachment(__DIR__.'/../UnitTest.php');
        $manager->addRequest($mail);
        $manager->send();
    }
}
