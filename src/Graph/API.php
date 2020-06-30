<?php

namespace TeamsConnector\Graph;

use Microsoft\Graph\Exception\GraphException;
use Microsoft\Graph\Model\Channel;
use Microsoft\Graph\Model\PlannerBucket;
use Microsoft\Graph\Model\PlannerPlan;
use Microsoft\Graph\Model\Team;
use TeamsConnector\Graph\Team\Channel\Message;
use TeamsConnector\Session\SessionInterface;

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

    private $retries = 0;

    private $session;

    public function __construct(string $clientSecret, string $clientId, SessionInterface $session, $redirectUri)
    {
        $this->session = $session;
        $this->clientSecret = $clientSecret;
        $this->clientId = $clientId;
        $this->accessToken = $this->session->get('accessToken');
        $this->refreshToken = $this->session->get('refreshToken');
        $this->tokenExpires = $this->session->get('tokenExpires');
        $this->redirectUri = $redirectUri;
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
             //   $this->refreshToken();
            }

            if ($code && $state) {
                $token = $this->getAccessTokenByCode($code, $state);

                if ($token) {
                    $this->accessToken = $token->getToken();
                    $this->refreshToken = $token->getRefreshToken();
                    $this->tokenExpires = $token->getExpires();
                    $this->session->set('accessToken', $this->accessToken);
                    $this->session->set('refreshToken', $this->refreshToken);
                    $this->session->set('tokenExpires', $this->tokenExpires);
                }
            }

            if ($this->accessToken == null) {
                echo json_encode(['url' => $this->getSignInUrl()]);
                http_response_code(400);
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
        $this->session->set('oauthState', $oauthClient->getState());

        return $authUrl;
    }

    public function getAccessTokenByCode(string $authCode, string $providedState)
    {

        // Validate state
        $expectedState = $this->session->get('oauthState');
        $this->session->set('oauthState', null);

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

        $this->session->set('accessToken', $token->getToken());
        $this->session->set('tokenExpires', $token->getExpires());
        $this->accessToken = $token->getToken();
        $this->tokenExpires = $token->getExpires();
    }

    /**
     * @param string $planId
     * @return PlannerPlan
     * @throws GraphException
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
     * @throws GraphException
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

    /**
     * @return Team[]
     * @throws GraphException
     */
    public function getJoinedTeams()
    {
        try {
            return $this->getGraphBeta()->createRequest('GET', '/me/joinedTeams')
                ->setReturnType(\Microsoft\Graph\Model\Team::class)
                ->execute();
        } catch (\Exception $e) {
            if ($this->shouldRetry($e)) {
                return $this->getJoinedTeams();
            }
        }
    }

    private function shouldRetry(\Exception $e)
    {
        if (substr_count($e->getMessage(), 401)) {
            if ($this->retries > 0) {
                throw $e;
            }

            $this->refreshToken();
            $this->retries++;
            return true;
        }
    }

    /**
     * @param Team $team
     * @return Channel[]
     * @throws GraphException
     */
    public function getChannelsByTeam(\Microsoft\Graph\Model\Team $team)
    {
        return $this->getGraphBeta()->createRequest('GET', '/teams/' . $team->getId() . '/channels')
            ->setReturnType(\Microsoft\Graph\Model\Channel::class)
            ->execute();
    }

    /**
     * @param Team $team
     * @param Channel $channel
     * @param Message $message
     * @return mixed
     * @throws GraphException
     */
    public function postMessage(\Microsoft\Graph\Model\Team $team, \Microsoft\Graph\Model\Channel $channel, Message $message)
    {
        return $this->getGraphBeta()->createRequest('POST', '/teams/' . $team->getId() . '/channels/' . $channel->getId() . '/messages')
            ->attachBody($message->getBody())
            ->execute();
    }
}