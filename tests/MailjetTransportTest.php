<?php

use PHPUnit\Framework\TestCase;
use Sboo\Laravel5Mailjet\Api\Mailjet;
use Sboo\Laravel5Mailjet\Transport\MailjetTransport;

class MailjetTransportTest extends TestCase
{
    public function testSend()
    {
        $expected = [
            'to' => ['to@example.net' => 'to whom!'],
            'cc' => ['cc@example.net' => 'cc whom!'],
            'bcc' => ['bcc@example.net' => 'bcc whom!'],
            'from' => ['from@email.com', 'from email!'],
            'replyto' => ['replyto@email.com', 'reply to!'],
            'subject' => 'My subject',
            'text' => 'This is the text',
            'html' => 'This is the <h1>HTML</h1>',
            'headers' => [
                'Content-Type' => 'multipart/alternative',
                'MIME-Version' => '1.0',
                'Subject' => 'My subject'
            ],
            // 'attachment' => $attachment,
            // 'inline_image' => []
        ];

        $message = new Swift_Message();
        $message->setTo('to@example.net', 'to whom!');
        $message->setCc('cc@example.net', 'cc whom!');
        $message->setBcc('bcc@example.net', 'bcc whom!');
        $message->addBcc('bcc2@example.net', 'bcc whom2!');
        $message->setFrom('from@email.com', 'from email!');
        $message->setReplyTo('replyto@email.com', 'reply to!');
        $message->setSubject('My subject');
        $message->setBody('This is the <h1>HTML</h1>', 'text/html');
        $message->addPart('This is the text', 'text/plain');

        $client = $this->getMockBuilder(Mailjet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transport = new MailjetTransport($client);
        $transport->send($message);
    }
}
