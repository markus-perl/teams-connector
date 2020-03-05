<?php

namespace TeamsConnector;

class API
{
    private $webhookUrl;

    public function __construct($webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;
    }

    /**
     * Sends card message as POST request
     *
     * @param CardInterface $card
     * @return bool
     * @throws Exception
     */
    public function send(CardInterface $card)
    {
        $response = $card->getResponse();
        $response['@context"']= 'http://schema.org/extensions';
        $response['@type"']= 'MessageCard';

        $json = json_encode($response);

        $curl = curl_init($this->webhookUrl);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SAFE_UPLOAD => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json)
            ],
        ));

        $result = curl_exec($curl);

        if ($result !== "1" || curl_errno($curl)) {
            throw new Exception('Teams API call failed: ' . curl_error($curl), curl_errno($curl));
        }

        curl_close($curl);
        return true;
    }
}