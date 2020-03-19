<?php

namespace TeamsConnector\Session;

class Session implements SessionInterface
{
    public function set(string $key, $value)
    {
        $_SESSION['teams_connector_' . $key] = $value;
    }

    public function get(string $key)
    {
        return isset($_SESSION['teams_connector_' . $key]) ? $_SESSION['teams_connector_' . $key] : null;
    }
}