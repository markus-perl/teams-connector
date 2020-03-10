<?php

namespace TeamsConnector\Graph\Team\Channel\Message\Card;

use TeamsConnector\Graph\Team\Channel\Message\Attachment;

class Hero implements Attachment
{

    private $id;

    private $contentType = 'application/vnd.microsoft.card.hero';

    private $title;

    private $subtitle;

    private $text;

    private $images = [];

    private $buttons = [];

    private $facts = [];

    public function __construct($title = null, $subtitle = null, $text = null, $image = null)
    {
        $this->id = uniqid('TeamsConnector');
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->text = $text;

        if ($image) {
            $this->addImage($image);
        }
    }

    public function addFact(string $label, string $value)
    {
        $this->facts[] = [
            'label' => $label,
            'value' => $value,
        ];
    }

    public function addImage(string $url)
    {
        $this->images[] = [
            'url' => $url
        ];
    }

    public function addButton(Attachment $button)
    {
        $this->buttons[] = $button->getBody();
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * @param mixed $subtitle
     */
    public function setSubtitle($subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param mixed $text
     */
    public function setText($text): void
    {
        $this->text = $text;
    }


    public function getBody(): array
    {

        $facts = '';
        if (count($this->facts)) {
            $facts = PHP_EOL .'<table><tbody>';
            foreach ($this->facts as $fact) {
                    $facts .= '<tr><td style="padding-right: 20px;"><strong>' . htmlentities($fact['label']) . '</strong></td><td>' . htmlentities($fact['value']) . '</td></tr>';
            }

            $facts .= '</tbody></table>';
        }

        return [
            'id' => $this->id,
            'contentType' => $this->contentType,
            'content' => json_encode(
                [
                    'title' => $this->title,
                    'subtitle' => $this->subtitle,
                    'text' => $this->text .$facts,
                    'images' => $this->images,
                    'buttons' => $this->buttons,
                ]
            ),
        ];
    }

    public function __toString()
    {
        return '<attachment id="' . $this->id . '"></attachment>';
    }

}