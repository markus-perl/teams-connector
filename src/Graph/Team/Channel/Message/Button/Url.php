<?php


namespace TeamsConnector\Graph\Team\Channel\Message\Button;


use TeamsConnector\Graph\Team\Channel\Message\Attachment;

class Url implements Attachment
{

    private $title;

    private $url;

    public function __construct(string $title, string $url)
    {
        $this->title = $title;
        $this->url = $url;
    }

    public function getBody(): array
    {
        return [
            'type' => 'openUrl',
            'title' => $this->title,
            'value' => $this->url,
        ];
    }
}