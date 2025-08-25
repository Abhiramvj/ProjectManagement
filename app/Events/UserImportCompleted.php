<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // if broadcasting
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
