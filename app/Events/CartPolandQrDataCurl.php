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

    // protected function generateToken($domain) 
    // {
    //     $secret_key = 'CvmJtiPdohSGs926';
    //     return hash_hmac('sha256', $domain, $secret_key);
    // }

    protected function fetchEntryData($qrCode)
    {  
        $payload = [
            "tabela"=> "rejestracja5",
            "kolumny"=> ["email", "telefon", "imie", "nazwisko"],
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