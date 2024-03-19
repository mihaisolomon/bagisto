<?php

declare(strict_types=1);

namespace Erathrive\EventsManager\Services\Client;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;

class Client
{
    protected HttpClient $client;

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * @throws GuzzleException
     */
    public function get(array $params)
    {
        $response = $this->client->request('GET', $params['url']);

        return json_decode((string) $response->getBody(), true);
    }
}
