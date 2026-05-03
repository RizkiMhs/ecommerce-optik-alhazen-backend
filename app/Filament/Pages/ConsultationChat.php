<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\ConsultationMessage;
use Filament\Pages\Page;

class ConsultationChat extends Page
{
    // Mengatur Icon dan Judul di Menu Sidebar Filament
    // Gunakan tipe data bawaan (Union Types) persis seperti yang diminta Filament v3
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string | \UnitEnum | null $navigationGroup = 'Layanan Pelanggan';

    protected static ?string $navigationLabel = 'Konsultasi Online';
    protected static ?string $title = 'Konsultasi Pelanggan';
    public $selectedUserId = null;
    public $replyMessage = '';

    // Pastikan baris ini ADA dan tidak terhapus:
    protected string $view = 'filament.pages.consultation-chat';

    // Mengambil daftar kustomer yang pernah chat
    public function getUsersProperty()
    {
        return User::whereHas('consultationMessages')->get();
    }

    // Mengambil isi pesan dari kustomer yang dipilih
    public function getMessagesProperty()
    {
        if (!$this->selectedUserId) return [];

        return ConsultationMessage::query()->where('user_id', $this->selectedUserId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    // Saat admin mengklik nama kustomer di sebelah kiri
    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;
        $this->replyMessage = '';
    }

    // Fungsi menyimpan balasan dari admin
    public function sendMessage()
    {
        $this->validate([
            'replyMessage' => 'required|string',
        ]);

        if ($this->selectedUserId) {
            ConsultationMessage::create([
                'user_id' => $this->selectedUserId,
                'message' => $this->replyMessage,
                'is_admin' => true, // Menandakan ini balasan Admin
            ]);

            $this->replyMessage = ''; // Kosongkan input setelah dikirim
        }
    }
}
