<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FairCreating
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $domain;
    public $jsonData;

    /**
     * Create a new event instance.
     */
    public function __construct($domain)
    {
        $this->domain = $domain;

        $this->jsonData = $this->fetchJsonData($domain);
    }

    protected function generateToken($domain) 
    {
        $secret_key = env('SECRET_KEY');
        return hash_hmac('sha256', $domain, $secret_key);
    }

    protected function fetchJsonData($domain)
    {   
        $token = $this->generateToken($domain);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $domain . '/wp-content/plugins/custom-element/bdg_stats/bdg_stats.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['last_id' => 99999, 'last_form' => 0, 'add_data' => true]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: ' . $token,
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        
        if ($response === false) {
            // Wyświetlenie szczegółów błędu
            $error = curl_error($ch);
            $error_no = curl_errno($ch);
            dd("cURL Error: {$error}, Code: {$error_no}");
        }

        curl_close($ch);
        return json_decode($response, true);
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
