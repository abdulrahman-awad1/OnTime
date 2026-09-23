<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $appointmentId,
    ) {}


    public function broadcastOn(): array
    {
        return [
            new Channel('assistant-dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'appointment.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'appointment_id' => $this->appointmentId,
        ];
    }
}
