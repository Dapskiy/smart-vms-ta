<div class="max-w-md mx-auto mt-10 bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold mb-4">Form Registrasi Tamu</h2>
    <p class="mb-4 text-gray-600">Tujuan Kunjungan: <strong>{{ $appointment->purpose }}</strong></p>

    @if (session()->has('success'))
        <div class="p-4 mb-4 text-green-700 bg-green-100 rounded-lg">
            {{ session('success') }}
        </div>
    @else
        <form wire:submit.prevent="submit">
            <div class="mb-4">
                <label class="block text-sm font-medium">Nama Lengkap</label>
                <input type="text" wire:model="name" class="w-full border rounded p-2" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium">Instansi / Perusahaan</label>
                <input type="text" wire:model="company" class="w-full border rounded p-2" required>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium">No. Telepon / WA</label>
                <input type="text" wire:model="phone" class="w-full border rounded p-2" required>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700">
                Kirim Data
            </button>
        </form>
    @endif
</div>
