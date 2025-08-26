<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable; // if broadcasting
use Illuminate\Queue\SerializesModels;

class UserImportCompleted implements ShouldBroadcast // remove ShouldBroadcast if not broadcasting
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function broadcastOn() // if ShouldBroadcast implemented
    {
        return new PrivateChannel('user-import');
    }
}
