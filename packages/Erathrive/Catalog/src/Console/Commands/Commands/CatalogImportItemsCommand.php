<?php

namespace Erathrive\Catalog\Console\Commands\Commands;

use Erathrive\Catalog\Repositories\Products\ConfigurableProductRepository;
use Erathrive\Catalog\Services\CatalogItemsReadEvents;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CatalogImportItemsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erathrive-app:catalog-import-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected CatalogItemsReadEvents $catalogItemsReadEvents;

    protected ConfigurableProductRepository $configurableProductRepository;

    public function __construct(
        CatalogItemsReadEvents $catalogItemsReadEvents,
        ConfigurableProductRepository $configurableProductRepository
    ) {
        $this->catalogItemsReadEvents = $catalogItemsReadEvents;

        $this->configurableProductRepository = $configurableProductRepository;

        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $eventCounter = $this->getEvent();
        $eventId = 1;
        if ($eventCounter['eventId']) {
            $eventId = $eventCounter['eventId'];
        }

        $this->readEvents($eventId);
    }

    protected function readEvents($lastEventId): void
    {
        $result = $this->catalogItemsReadEvents->execute($lastEventId);

        if (in_array($result['type'], ['product_created', 'product_updated'])) {

        }

        $this->comment('Event type: ' . $result['type'] . ' with number: '.  $lastEventId);
        if ($result['next']) {
            $this->comment('Reading event number: ' . $lastEventId);
            if (in_array($result['type'], ['product_created', 'product_updated'])) {
                if (count($result['data']) > 1) {
                   $this->configurableProductRepository->crateProduct($result['data']);
                }
            }

            //$this->create($result);

            Storage::disk('local')->put($this->getEventsFilePath(), json_encode(['eventId' => $result['next']]));
            $this->readEvents($result['next']);
        } else {
            $eventCounter = $this->getEvent();
            if (!array_key_exists('hasBeenRead', $eventCounter)) {
                $this->comment('Reading event number: ' . $result['data']['stored-event-id']);
                $eventCounter['hasBeenRead'] = true;
                Storage::disk('local')->put($this->getEventsFilePath(), json_encode($eventCounter));
                //$this->create($result);
            }
        }
    }
    protected function getEvent()
    {
        $filePath = $this->getEventsFilePath();
        if (!Storage::disk('local')->exists($filePath)) {
            Storage::disk('local')->put($filePath, json_encode(['eventId' => null]));
        }
        return json_decode(Storage::disk('local')->get($filePath), true);
    }

    protected function getEventsFilePath(): string
    {
        return "events/last_event.json";
    }
}
