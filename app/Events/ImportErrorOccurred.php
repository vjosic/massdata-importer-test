<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportErrorOccurred
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $importRecord;
    public $errorMessage;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct($importRecord, $errorMessage, $userId)
    {
        $this->importRecord = $importRecord;
        $this->errorMessage = $errorMessage;
        $this->userId = $userId;
    }
}
