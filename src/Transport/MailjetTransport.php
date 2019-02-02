<?php

namespace Sboo\Laravel5Mailjet\Transport;

use Illuminate\Mail\Transport\Transport;
use Sboo\Laravel5Mailjet\Api\Mailjet;
use Swift_Attachment;
use Swift_Mime_SimpleMessage;
use Swift_MimePart;
use Swift_Mime_Headers_UnstructuredHeader;

class MailjetTransport extends Transport
{
    /**
     * The Mailjet instance.
     *
     * @var \Sboo\Laravel5Mailjet\Api\Mailjet
     */
    protected $mailjet;

    /**
     * Create a new Mailjet transport instance.
     *
     * @param  \Sboo\Laravel5Mailjet\Api\Mailjet  $mailin
     * @return void
     */
    public function __construct(Mailjet $mailjet)
    {
        $this->mailjet = $mailjet;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $response = $this->mailjet->send(
            $this->buildData($message)
        );

        var_dump($this->mailjet->getResponseCode(), $response);

        if (!$response) {
            throw new MailjetTransportException("Unknown error");
        }

        if ($res['code'] != 'success') {
            throw new MailjetTransportException($res['message']);
        }

        // Should return the number of recipients who were accepted for delivery.
        return 0;
    }

    /**
     * Transforms Swift_Message into data array for SendinBlue's API
     * cf. https://apidocs.sendinblue.com/tutorial-sending-transactional-email/
     *
     * @todo implements headers, inline_image
     * @param  Swift_Mime_SimpleMessage $message
     * @return array
     */
    protected function buildData($message)
    {
        $data = [
            'method' => 'POST'
        ];

        if ($message->getHeaders()) {
            $headers = $message->getHeaders()->getAll();
            foreach( $headers as $header) {
                if( $header instanceof Swift_Mime_Headers_UnstructuredHeader ) {
                    $data['Headers'][$header->getFieldName()] = $header->getValue();
                }
            }
        }

        if ($message->getTo()) {
            $data['To'] = $this->toCommaSeparatedString($message->getTo());
        }

        if ($message->getSubject()) {
            $data['Subject'] = $message->getSubject();
        }

        if ($message->getFrom()) {
            $from = $message->getFrom();
            reset($from);
            $key = key($from);
            $data['From'] = [$key, $from[$key]];
        }

        // set content
        if ($message->getContentType() == 'text/plain') {
            $data['Text-part'] = $message->getBody();
        } else {
            $data['Html-part'] = $message->getBody();
        }

        $children = $message->getChildren();
        foreach ($children as $child) {
            if ($child instanceof Swift_MimePart && $child->getContentType() == 'text/plain') {
                $data['Text-part'] = $child->getBody();
            }
        }

        if (! isset($data['Text-part'])) {
            $data['Text-part'] = strip_tags($message->getBody());
        }
        // end set content

        if ($message->getCc()) {
            $data['Cc'] = $this->toCommaSeparatedString($message->getCc());
        }

        if ($message->getBcc()) {
            $data['Bcc'] = $this->toCommaSeparatedString($message->getBcc());
        }

        if ($message->getReplyTo()) {
            $replyTo = $message->getReplyTo();
            reset($replyTo);
            $key = key($replyTo);
            $data['ReplyTo'] = [$key, $replyTo[$key]];
        }

        // attachment
        $attachment = [];
        foreach ($children as $child) {
            if ($child instanceof Swift_Attachment) {
                $filename = $child->getFilename();
                $content = chunk_split(base64_encode($child->getBody()));
                $attachment[$filename] = $content;
            }
        }

        if (count($attachment)) {
            $data['attachment'] = $attachment;
        }

        return $data;
    }

    protected function toCommaSeparatedString($addresses) {
        return collect($addresses)
            ->map(function ($name, $email) {
                if (is_null($name))
                    return $email;
                return sprintf('"%s" <%s>', $name, $email);
            })
            ->implode(',');
    }
}
