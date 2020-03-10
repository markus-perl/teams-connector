<?php


namespace TeamsConnector\Webhook\Card\Section;


use TeamsConnector\Webhook\Card\Action\ActionInterface;

class DetailedMessage implements SectionInterface
{

    private $title;

    private $subtitle;

    private $image;

    private $facts = [];

    private $actions = [];

    public function addAction(ActionInterface $action)
    {
        $this->actions[] = $action->getResponse();
    }

    public function __construct(string $title = null, string $subtitle = null, string $image = null)
    {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->image = $image;
    }

    public function addFact(string $name, string $value = null)
    {
        $this->facts[] = ['name' => $name, 'value' => $value];
    }

    public function getResponse(): array
    {
        return [
            'activityTitle' => $this->title,
            'activitySubtitle' => $this->subtitle,
            'activityImage' => $this->image,
            'facts' => $this->facts,
            'potentialAction' => $this->actions,
        ];
    }

}