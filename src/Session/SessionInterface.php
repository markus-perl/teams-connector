<?php

namespace TeamsConnector\Session;

interface SessionInterface
{
    public function set(string $key, $value);

    public function get(string $key);
}