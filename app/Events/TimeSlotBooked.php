<?php

namespace App\Events;

use App\Models\TimeSlot;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimeSlotBooked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $timeSlotId,
        public int $clinicLocationId,
    ) {}

    /**
     * القناة اللي الحدث ده هيتبث عليها.
     * كل عيادة ليها قناة خاصة بيها، عشان مريض فاتح صفحة
     * عيادة معينة ميستقبلش تحديثات عيادات تانية مالوش دعوة بيها.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('clinic.'.$this->clinicLocationId),
        ];
    }

    /**
     * اسم الحدث زي ما هيوصل للفرونت - لو معملناهاش، Laravel
     * هيستخدم اسم الكلاس بالكامل (App\Events\TimeSlotBooked)
     * وده أطول وأصعب في التعامل معاه في الجافاسكريبت.
     */
    public function broadcastAs(): string
    {
        return 'time-slot.booked';
    }

    /**
     * البيانات اللي فعلياً هتوصل للفرونت مع الحدث.
     */
    public function broadcastWith(): array
    {
        return [
            'time_slot_id' => $this->timeSlotId,
            'status' => 'booked',
        ];
    }
}
