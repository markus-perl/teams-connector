<?php


namespace TeamsConnector\Webhook\Card\Action;


class OpenUri implements ActionInterface
{

    private $name;

    private $uri;

    public function __construct(string $name, string $uri)
    {
        $this->name = $name;
        $this->uri = $uri;
    }

    public function getResponse(): array
    {
        return [
            '@type' => 'OpenUri',
            'name' => $this->name,
            'targets' => [
                [
                    'os' => 'default',
                    'uri' => $this->uri,
                ],
            ],
        ];
    }
}