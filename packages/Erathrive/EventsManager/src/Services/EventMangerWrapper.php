<?php

namespace Erathrive\EventsManager\Services;

class EventMangerWrapper
{
    protected Client\Client $client;

    public function __construct(Client\Client $client)
    {
        $this->client = $client;
    }

    public function get(array $params): void
    {
        $this->client->get([
            'url' => env('EVENT_URL') . 'clients/' . $params['uuid'],
            'data' => $params['data']
        ]);
    }
}
