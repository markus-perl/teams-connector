<?php

namespace TeamsConnector\Webhook;

use TeamsConnector\Exception;

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
     * @param bool $sendInBackground Dabei kann die Zustellung nicht garantiert werden, der Versand geht aber schneller
     * @return bool
     * @throws Exception
     */
    public function send(CardInterface $card, $sendInBackground = false)
    {
        $response = $card->getResponse();
        $response['@context'] = 'http://schema.org/extensions';
        $response['@type'] = 'MessageCard';

        $json = json_encode($response);

        $curlOptions = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SAFE_UPLOAD => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=UTF-8',
                'Content-Length: ' . strlen($json)
            ],
        );

        if ($sendInBackground) {
            unset($curlOptions[CURLOPT_TIMEOUT_MS]);
            $curlOptions[CURLOPT_RETURNTRANSFER] = false;
            $curlOptions[CURLOPT_FORBID_REUSE] = true;
            $curlOptions[CURLOPT_CONNECTTIMEOUT] = 1;
            $curlOptions[CURLOPT_TIMEOUT_MS] = 500;
            $curlOptions[CURLOPT_HEADER] = false;
        }

        $curl = curl_init($this->webhookUrl);
        curl_setopt_array($curl, $curlOptions);

        $result = curl_exec($curl);

        if (!$sendInBackground) {
            if ($result !== "1" || curl_errno($curl)) {
                throw new Exception('Teams API call failed: ' . curl_error($curl) . ' - ' . $result, curl_errno($curl));
            }
        }

        curl_close($curl);

        if ($sendInBackground && $forkingWorked) {
            exit;
        }

        return true;
    }
}