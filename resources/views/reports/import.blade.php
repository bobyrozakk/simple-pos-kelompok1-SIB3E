@extends('layouts.app')

@section('title', 'Import Produk')

@section('content')
<h1 class="text-lg font-semibold mb-4">Import Produk dari CSV</h1>

@if (session('success'))
    <div class="bg-green-50 text-green-700 p-3 rounded-md mb-4">
        {{ session('success') }}
    </div>
@endif

<form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data" class="max-w-md">
    @csrf
    <label class="block mb-3">
        <span class="text-sm font-medium">File CSV</span>
        <input type="file" name="csv" accept=".csv" class="mt-1 block w-full text-sm">
        @error('csv')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </label>
    <p class="text-xs text-slate-500 mb-3">Column yang dibutuhkan: <code>name</code>, <code>category_id</code>, <code>price</code>.</p>
    <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-md text-sm">Import</button>
</form>

@if ($importErrors->isNotEmpty())
    <div class="mt-8">
        <h2 class="text-base font-semibold mb-2">Row yang gagal di-import ({{ $importErrors->count() }})</h2>
        <table class="w-full text-sm border">
            <thead class="bg-slate-100">
                <tr>
                    <th class="border px-3 py-2 text-left">Row</th>
                    <th class="border px-3 py-2 text-left">Isi row</th>
                    <th class="border px-3 py-2 text-left">Pesan error</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($importErrors as $error)
                    <tr>
                        <td class="border px-3 py-2">{{ $error->row_number }}</td>
                        <td class="border px-3 py-2 font-mono">{{ $error->row_data }}</td>
                        <td class="border px-3 py-2 text-red-600">{{ $error->message }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
