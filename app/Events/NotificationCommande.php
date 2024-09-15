<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCommande implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $commande;

    public function __construct($commande)
    {
        $this->commande = $commande;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('commandes');
    }

    public function broadcastWith()
    {
        return [
            'commande_id' => $this->commande->id,
            'message' => 'Une nouvelle commande a été passée !'
        ];
    }
}
