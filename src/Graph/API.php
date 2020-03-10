<?php
namespace TeamsConnector\Graph;

use Microsoft\Graph\Model\PlannerBucket;
use Microsoft\Graph\Model\PlannerPlan;
use TeamsConnector\Graph\Team\Channel\Message;

class API
{
    private $accessToken = null;
    private $refreshToken = null;
    private $tokenExpires = null;
    private $clientSecret = null;
    private $clientId = null;
    private $oauthAuthority = 'https://login.microsoftonline.com/common';
    private $oauthAuthorizeEndpoint = '/oauth2/v2.0/authorize';
    private $oauthTokenEndpoint = '/oauth2/v2.0/token';
    private $redirectUri = 'http://localhost/';
    private $scopes = 'openid profile offline_access user.read calendars.read group.read.all group.readwrite.all';

    public function __construct(string $clientSecret, string $clientId)
    {
        $this->clientSecret = $clientSecret;
        $this->clientId = $clientId;
        $this->accessToken = $_SESSION['ms_access_token'] ?? null;
        $this->refreshToken = $_SESSION['ms_refresh_token'] ?? null;
        $this->tokenExpires = $_SESSION['ms_token_expires'] ?? null;
    }

    /**
     * Redirect zum MS Login Screen, wenn erforderlich
     */
    public function signIn()
    {
        $code = isset($_GET['code']) ? $_GET['code'] : null;
        $state = isset($_GET['state']) ? $_GET['state'] : null;

        if (!$this->accessToken || $this->tokenExpires < time()) {

            //Token aktualisieren, wenn abgelaufen oder kurz vorm ablaufen
            if ($this->tokenExpires > 0 && $this->tokenExpires < time() - 60) {
                $this->refreshToken();
            }

            if ($code && $state) {
                $token = $this->getAccessTokenByCode($code, $state);
                if ($token) {
                    $this->accessToken = $token->getToken();
                    $this->refreshToken = $token->getRefreshToken();
                    $this->tokenExpires = $token->getExpires();
                    $_SESSION['ms_access_token'] = $this->accessToken;
                    $_SESSION['ms_refresh_token'] = $this->refreshToken;
                    $_SESSION['ms_token_expires'] = $this->tokenExpires;
                }
            }

            if ($this->accessToken == null) {
                header('Location: ' . $this->getSignInUrl());
                exit;
            }
        }
    }

    /**
     * @return \Microsoft\Graph\Graph
     */
    public function getGraph()
    {
        $graph = new \Microsoft\Graph\Graph();
        $graph->setAccessToken($this->accessToken);
        return $graph;
    }

    /**
     * @return \Microsoft\Graph\Graph
     */
    public function getGraphBeta()
    {
        $graph = new \Microsoft\Graph\Graph();
        $graph->setAccessToken($this->accessToken);
        $graph->setApiVersion('beta');
        return $graph;
    }

    public function getSignInUrl()
    {
        $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri' => $this->redirectUri,
            'urlAuthorize' => $this->oauthAuthority . $this->oauthAuthorizeEndpoint,
            'urlAccessToken' => $this->oauthAuthority . $this->oauthTokenEndpoint,
            'urlResourceOwnerDetails' => '',
            'scopes' => $this->scopes,
        ]);

        $authUrl = $oauthClient->getAuthorizationUrl();

        // Save client state so we can validate in callback
        $_SESSION['oauthState'] = $oauthClient->getState();

        return $authUrl;
    }

    public function getAccessTokenByCode(string $authCode, string $providedState)
    {
        // Validate state
        $expectedState = $_SESSION['oauthState'];
        $_SESSION['oauthState'] = null;

        if (!isset($expectedState)) {
            echo 'expected state missing';
            return false;
        }

        if (!isset($providedState) || $expectedState != $providedState) {
            echo 'wrong state';
            return false;
        }


        // Authorization code should be in the 'code' query param
        if ($authCode) {
            // Initialize the OAuth client
            $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
                'redirectUri' => $this->redirectUri,
                'urlAuthorize' => $this->oauthAuthority . $this->oauthAuthorizeEndpoint,
                'urlAccessToken' => $this->oauthAuthority . $this->oauthTokenEndpoint,
                'urlResourceOwnerDetails' => '',
                'scopes' => $this->scopes,
            ]);

            try {
                // Make the token request
                return $oauthClient->getAccessToken('authorization_code', [
                    'code' => $authCode
                ]);
            } catch (League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                echo $e;
                return false;
            }
        }
    }

    public function refreshToken()
    {
        $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri' => $this->redirectUri,
            'urlAuthorize' => $this->oauthAuthority . $this->oauthAuthorizeEndpoint,
            'urlAccessToken' => $this->oauthAuthority . $this->oauthTokenEndpoint,
            'urlResourceOwnerDetails' => '',
        ]);

        $token = $oauthClient->getAccessToken('refresh_token', [
            'refresh_token' => $this->refreshToken,
        ]);

        $_SESSION['ms_access_token'] = $token->getToken();
        $_SESSION['ms_token_expires'] = $token->getExpires();
    }

    /**
     * @param string $planId
     * @return PlannerPlan
     * @throws \Microsoft\Graph\Exception\GraphException
     */
    public function getPlan(string $planId)
    {
        return $this->getGraphBeta()->createRequest('GET', '/planner/plans/' . $planId)
            ->setReturnType(PlannerPlan::class)
            ->execute();
    }


    public function getPlanBuckets(PlannerPlan $plan)
    {
        return $this->getGraph()->createRequest('GET', '/planner/plans/' . $plan->getId() . '/buckets')
            ->setReturnType(PlannerBucket::class)
            ->execute();
    }

    /**
     * @param PlannerPlan $plan
     * @param PlannerBucket $bucket
     * @param string $title
     * @return mixed
     * @throws \Microsoft\Graph\Exception\GraphException
     */
    public function createPlannerTask(PlannerPlan $plan, PlannerBucket $bucket, string $title)
    {
        return $this->getGraph()->createRequest('POST', '/planner/tasks')
            ->attachBody([
                'planId' => $plan->getId(),
                'bucketId' => $bucket->getId(),
                'title' => $title,
            ])
            ->execute();
    }

    public function getJoinedTeams()
    {
        return $this->getGraphBeta()->createRequest('GET', '/me/joinedTeams')
            ->setReturnType(\Microsoft\Graph\Model\Team::class)
            ->execute();
    }

    public function getChannelsByTeam(\Microsoft\Graph\Model\Team $team) {
        return $this->getGraphBeta()->createRequest('GET', '/teams/' . $team->getId() . '/channels')
            ->setReturnType(\Microsoft\Graph\Model\Channel::class)
            ->execute();
    }

    public function postMessage(\Microsoft\Graph\Model\Team $team, \Microsoft\Graph\Model\Channel $channel, Message $message)
    {
        return $this->getGraphBeta()->createRequest('POST', '/teams/' . $team->getId() . '/channels/' . $channel->getId() . '/messages')
            ->attachBody($message->getBody())
            ->execute();
    }
}