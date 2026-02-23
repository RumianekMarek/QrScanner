<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CartPolandQrDataCurl
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $returner;

    /**
     * Create a new event instance.
     */
    public function __construct($qrCode)
    {
        $this->returner = $this->fetchEntryData($qrCode);
    }

    protected function fetchEntryData($qrCode)
    {  
        $payload = [
            "tabela"=> "rejestracja5",
            "kolumny"=> ["email", "telefon", "imie", "nazwisko", "ulica", "numer", "kod_pocztowy", "miasto", "kraj", "nip", "zainteresowania1", "zainteresowania2", "zainteresowania3", "zainteresowania4", "zainteresowania5", "zainteresowania6", "zainteresowania7", "zainteresowania8", "zainteresowania9"
            ],
            "filtry"=> [
                ["kod", $qrCode],
            ],
            "limit"=> 1
        ];

        $response = Http::withBasicAuth('s.skrypnychenko', 'Saltarello2025!')
            ->post('https://ptak185.ticketpoland.pl/import/api/api2/json.php', $payload);

        if ($response->successful()) {
            $wynik = $response->json();
        } else {
            return "Błąd: " . $response;
        }

        return $wynik;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}