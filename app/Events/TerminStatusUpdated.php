<?php

namespace App\Events;

use App\Models\Termin;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TerminStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $termin;

    public function __construct(Termin $termin)
    {
        $this->termin = $termin;
    }
} 