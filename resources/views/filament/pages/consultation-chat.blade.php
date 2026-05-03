<x-filament-panels::page>
    <!-- Container Utama: Membagi layar Kiri (30%) dan Kanan (70%) -->
    <div style="display: flex; flex-direction: row; gap: 1.5rem; height: 70vh; min-height: 500px; width: 100%;">

        <!-- BAGIAN KIRI: DAFTAR PELANGGAN -->
        <!-- BAGIAN KIRI: DAFTAR PELANGGAN -->
        <div style="width: 30%; display: flex; flex-direction: column; overflow: hidden; border-right: 1px solid #e5e7eb;" class="bg-white dark:bg-gray-900 dark:border-white/10">
            <!-- Header Kiri -->
            <div style="padding: 1.25rem 1rem; border-bottom: 1px solid #e5e7eb; font-weight: bold; font-size: 1.1rem;" class="dark:border-white/10 dark:text-white">
                Daftar Pelanggan
            </div>
            
            <!-- List Pelanggan -->
            <div style="overflow-y: auto; flex: 1; padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem; background-color: #f9fafb;" class="dark:bg-gray-950/50">
                @forelse($this->users as $user)
                    <button 
                        wire:click="selectUser({{ $user->id }})"
                        style="width: 100%; text-align: left; padding: 0.75rem; border-radius: 0.75rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.75rem;
                            {{ $selectedUserId == $user->id 
                                ? 'background-color: #eef2ff; border: 1px solid #6366f1; box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.1);' 
                                : 'background-color: #ffffff; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);' }}"
                        class="{{ $selectedUserId == $user->id ? 'dark:bg-indigo-900/20 dark:border-indigo-500' : 'dark:bg-gray-800 dark:border-gray-700' }} hover:border-indigo-300 dark:hover:border-indigo-500"
                    >
                        <!-- Avatar Bulat (Huruf Pertama Nama) -->
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1rem; flex-shrink: 0;
                            {{ $selectedUserId == $user->id ? 'background-color: #6366f1; color: white;' : 'background-color: #f3f4f6; color: #6b7280;' }}"
                            class="{{ $selectedUserId == $user->id ? '' : 'dark:bg-gray-700 dark:text-gray-300' }}"
                        >
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>

                        <!-- Teks Nama & Email -->
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 600; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; 
                                color: {{ $selectedUserId == $user->id ? '#4338ca' : '#1f2937' }};"
                                class="{{ $selectedUserId == $user->id ? 'dark:text-indigo-400' : 'dark:text-gray-200' }}"
                            >
                                {{ $user->name }}
                            </div>
                            <div style="font-size: 0.75rem; opacity: 0.7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #6b7280;" class="dark:text-gray-400">
                                {{ $user->email }}
                            </div>
                        </div>

                        <!-- Indikator Titik Aktif -->
                        @if($selectedUserId == $user->id)
                            <div style="width: 0.5rem; height: 0.5rem; border-radius: 9999px; background-color: #6366f1; flex-shrink: 0;"></div>
                        @endif
                    </button>
                @empty
                    <div style="text-align: center; padding: 2rem; opacity: 0.5; font-size: 0.875rem;" class="dark:text-white">
                        Belum ada obrolan.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- BAGIAN KANAN: RUANG CHAT -->
        <div style="width: 70%; display: flex; flex-direction: column; overflow: hidden;" class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            @if($selectedUserId)
                <!-- Header Chat (Nama Kustomer) -->
                <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 0.5rem; font-weight: bold;" class="dark:border-white/10 dark:text-white">
                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: #22c55e; box-shadow: 0 0 5px #22c55e;"></span>
                    {{ $this->users->find($selectedUserId)->name ?? 'Pelanggan' }}
                </div>

                <!-- Area Balon Chat -->
                <div style="flex: 1; overflow-y: auto; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; background-color: #f9fafb;" class="dark:bg-gray-950" wire:poll.3s>
                    @foreach($this->messages as $msg)
                        <!-- Penentu Posisi: Kanan (Admin) atau Kiri (Pelanggan) -->
                        <div style="display: flex; width: 100%; justify-content: {{ $msg->is_admin ? 'flex-end' : 'flex-start' }};">
                            
                            <!-- Desain Balon Chat -->
                            <div style="max-width: 75%; padding: 0.75rem 1rem; border-radius: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                                {{ $msg->is_admin 
    ? 'background-color: #6366f1; color: white; border-bottom-right-radius: 0.1rem;' 
    : 'background-color: white; color: #1f2937; border-bottom-left-radius: 0.1rem; border: 1px solid #e5e7eb;' }}"
                                class="{{ !$msg->is_admin ? 'dark:bg-gray-800 dark:text-white dark:border-gray-700' : '' }}"
                            >
                                <p style="margin: 0; font-size: 0.875rem; white-space: pre-wrap; line-height: 1.4;">{{ $msg->message }}</p>
                                <span style="display: block; font-size: 0.65rem; margin-top: 0.4rem; text-align: {{ $msg->is_admin ? 'right' : 'left' }}; opacity: 0.7;">
                                    {{ $msg->created_at->format('H:i') }}
                                </span>
                            </div>

                        </div>
                    @endforeach
                </div>

                <!-- Form Ketik Balasan -->
                <div style="padding: 1rem; border-top: 1px solid #e5e7eb; background-color: white;" class="dark:border-white/10 dark:bg-gray-900">
                    <form wire:submit.prevent="sendMessage" style="display: flex; gap: 0.75rem; align-items: center;">
                        <div style="flex: 1;">
                            <x-filament::input.wrapper>
                                <x-filament::input
                                    type="text"
                                    wire:model="replyMessage"
                                    placeholder="Ketik balasan untuk pelanggan..."
                                    required
                                />
                            </x-filament::input.wrapper>
                        </div>
                        <x-filament::button type="submit" icon="heroicon-m-paper-airplane">
                            Kirim
                        </x-filament::button>
                    </form>
                </div>
            @else
                <!-- Tampilan Jika Belum Memilih Kustomer -->
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0.5;" class="dark:text-white">
                    <x-filament::icon icon="heroicon-o-chat-bubble-left-right" style="width: 5rem; height: 5rem; margin-bottom: 1rem; color: #9ca3af;" />
                    <p style="font-size: 1rem;">Pilih pelanggan di menu sebelah kiri untuk memulai obrolan.</p>
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>