<?php

declare(strict_types=1);

namespace Erathrive\Catalog\Services;

use Erathrive\EventsManager\Services\Client\Client;
use GuzzleHttp\Exception\GuzzleException;

class CatalogItemsReadEvents
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @throws GuzzleException
     */
    public function execute($eventId)
    {
        return $this->client->get([
            'url' => env('EVENT_URL') . 'events/' . $eventId,
        ]);
    }
}
