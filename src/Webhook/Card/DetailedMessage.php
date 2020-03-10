<?php

namespace TeamsConnector\Webhook\Card;


use TeamsConnector\Webhook\Card\Section\SectionInterface;
use TeamsConnector\Webhook\CardInterface;
use TeamsConnector\Exception;

class DetailedMessage implements CardInterface
{

    private $title;

    private $subtitle;

    private $image;

    private $summary;

    private $sections = [];


    /**
     * Message constructor.
     * @param string $title
     * @param string $text
     * @throws Exception
     */
    public function __construct(string $title, string $text)
    {
        if (mb_strlen($text) < 1 || mb_strlen($title) < 1) {
            throw new Exception('title and text must be set with a minimum length of 1');
        }

        $this->text = $text;
        $this->title = $title;
    }

    public function addSection(SectionInterface $section)
    {
        $this->sections[] = $section->getResponse();
    }

    public function getResponse(): array
    {
        return [
            'title' => $this->title,
            'text' => $this->text,
            'sections' => $this->sections,
        ];
    }

}