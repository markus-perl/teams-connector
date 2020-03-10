<?php

namespace TeamsConnector\Graph\Team\Channel;


use Microsoft\Graph\Model\Attachment;

/**
 * Class Message
 * @package TeamsConnector\Graph\Team\Channel
 *
 */
class Message
{

    private $content;

    /**
     * @var Attachment[]
     */
    private $attachments;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function addAttachment(\TeamsConnector\Graph\Team\Channel\Message\Attachment $attachment)
    {
        $this->attachments[] = $attachment->getBody();
    }

    public function getBody()
    {
        return [
            'body' => [
                "content" => $this->content,
                'contentType' => 'html',
            ],
            'attachments' => $this->attachments,
        ];
    }
}