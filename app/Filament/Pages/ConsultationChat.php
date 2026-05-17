<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\ConsultationMessage;
use Filament\Pages\Page;
use Livewire\WithFileUploads; // 💡 1. Wajib import trait file upload Livewire

class ConsultationChat extends Page
{
    use WithFileUploads; // 💡 2. Pasang trait di dalam class

    // Mengatur Icon dan Judul di Menu Sidebar Filament
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string | \UnitEnum | null $navigationGroup = 'Layanan Pelanggan';

    protected static ?string $navigationLabel = 'Konsultasi Online';
    protected static ?string $title = 'Konsultasi Pelanggan';
    
    // Pastikan baris ini ADA dan tidak terhapus:
    protected string $view = 'filament.pages.consultation-chat';

    public $selectedUserId = null;
    public $replyMessage = '';
    public $image; // 💡 3. Variabel penampung gambar sementara

    // Mengambil daftar kustomer yang pernah chat
    public function getUsersProperty()
    {
        return User::whereHas('consultationMessages')->get();
    }

    // Mengambil isi pesan dari kustomer yang dipilih
    public function getMessagesProperty()
    {
        if (!$this->selectedUserId) return [];

        return ConsultationMessage::query()
            ->where('user_id', $this->selectedUserId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    // Saat admin mengklik nama kustomer di sebelah kiri
    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;
        $this->replyMessage = '';
        $this->image = null; // 💡 Kosongkan gambar jika admin berpindah pelanggan
    }

    // Fungsi menyimpan balasan dari admin
    public function sendMessage()
    {
        // 💡 4. Update validasi (gambar dibatasi max 2MB, teks boleh kosong jika kirim gambar)
        $this->validate([
            'replyMessage' => 'nullable|string',
            'image' => 'nullable|image|max:2048', 
        ]);

        // Jika teks dan gambar keduanya kosong, jangan lakukan apa-apa
        if (empty($this->replyMessage) && empty($this->image)) {
            return;
        }

        if ($this->selectedUserId) {
            
            // 💡 5. Logika penyimpanan gambar
            $imagePath = null;
            if ($this->image) {
                // Simpan ke storage/app/public/chat_images
                $imagePath = $this->image->store('chat_images', 'public');
            }

            ConsultationMessage::create([
                'user_id' => $this->selectedUserId,
                'message' => $this->replyMessage ?? '',
                'is_admin' => true, 
                'image' => $imagePath, // 💡 Simpan nama file ke database
            ]);

            // 💡 6. Kosongkan form dan gambar setelah berhasil dikirim
            $this->replyMessage = '';
            $this->image = null; 
        }
    }
}