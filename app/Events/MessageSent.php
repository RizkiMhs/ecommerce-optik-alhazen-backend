<?php

namespace App\Events;

use App\Models\ConsultationMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // <-- Perhatikan tambahan kata 'Now'
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Tambahkan "implements ShouldBroadcastNow" agar dikirim detik itu juga secara Real-Time
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Saat event ini dipanggil, kita akan menitipkan data pesan (ConsultationMessage) ke dalamnya
     */
    public function __construct(ConsultationMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Tentukan di "Pipa" (Channel) mana alarm ini akan dibunyikan.
     */
    public function broadcastOn(): array
    {
        // Kita membuat pipa khusus untuk setiap kustomer. 
        // Contoh: Jika kustomer dengan ID 5 chat, maka pipanya bernama 'chat.5'
        return [
            new Channel('chat.' . $this->message->user_id),
        ];
    }

    /**
     * (Opsional) Nama alias untuk alarm ini agar mudah ditangkap oleh Flutter / Filament
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}