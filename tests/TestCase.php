<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $defaultHeaders = [
        'Origin' => 'http://localhost:5173',
        'Referer' => 'http://localhost:5173/',
    ];

    protected $withCredentials = true;

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $response = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);

        if ($cookie = $response->getCookie(config('session.cookie'), decrypt: false)) {
            $this->withUnencryptedCookie(config('session.cookie'), $cookie->getValue());
        }

        return $response;
    }
}
