@php
    $dailyMenuItems = $getState('daily_menu_items') ?? [];
@endphp

@if(count($dailyMenuItems) > 0)
    <table class="w-full border-collapse">
        <thead>
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Nama Menu</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Target Jumlah</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Status</th>
        </tr>
        </thead>
        <tbody>
        @foreach($dailyMenuItems as $item)
            <tr>
                <td class="border border-gray-300 px-4 py-2">{{ $item['menu_name'] }}</td>
                <td class="border border-gray-300 px-4 py-2">{{ $item['qty'] }}</td>
                <td class="border border-gray-300 px-4 py-2">Belum Diproduksi</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p class="mt-4 text-gray-600">Simpan laporan ini untuk mengisi jumlah produksi aktual.</p>
@else
    <div class="text-gray-500">Tidak ada data menu yang tersedia.</div>
@endif
