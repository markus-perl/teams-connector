<?php

namespace TeamsConnector\Webhook\Card;


use TeamsConnector\Webhook\CardInterface;
use TeamsConnector\Webhook\Exception;

class Message implements CardInterface
{

    private $title;

    private $text;

    /**
     * Message constructor.
     * @param string $title
     * @param string $text
     * @throws Exception
     */
    public function __construct(string $title, string $text)
    {
        if (mb_strlen($title) < 1 || mb_strlen($text) < 1) {
            throw new Exception('title and text must be set with a minimum length of 1');
        }

        $this->title = $title;
        $this->text = $text;
    }

    public function getResponse(): array
    {
        return [
            "title" => $this->title,
            "text" => $this->text,
        ];
    }

}