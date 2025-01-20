<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoginToken
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $login_token;

    /**
     * Create a new event instance.
     */
    public function __construct()
    {
        $this->login_token = $this->createToken();
    }

    public function createToken()
    {
        $token_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $token = '';
        $max = strlen($token_chars) - 1;
        
        do {
            for ($i = 0; $i < 7; $i++) {
                $token .= $token_chars[random_int(0, $max)];
            }

            $check_tokens = User::where('login_token', $token)->first();

        } while ($check_tokens !== null);
        
        return $token;
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
