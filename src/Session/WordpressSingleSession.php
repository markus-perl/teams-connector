<?php

namespace TeamsConnector\Session;

class WordpressSingleSession implements SessionInterface
{
    public function set(string $key, $value)
    {
        update_option('teams_connector_' . $key, $value);
    }

    public function get(string $key)
    {
        return get_option('teams_connector_' . $key, null);
    }
}