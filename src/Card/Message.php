<?php

namespace TeamsConnector\Card;


use TeamsConnector\CardInterface;

class Message implements CardInterface
{

    private $title;

    private $text;

    public function __construct(string $title, string $text)
    {
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