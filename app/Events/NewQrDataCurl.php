<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewQrDataCurl
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
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://backend-production-df8c.up.railway.app/api/v1/qr-verify/' . $qrCode);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $decoded = json_decode(curl_exec($ch));

        curl_close($ch);
        return $decoded;
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