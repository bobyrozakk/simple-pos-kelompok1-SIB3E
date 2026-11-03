@extends('layouts.app')

@section('title', 'Kategori')

@section('content')
<h1 class="text-lg font-semibold mb-4">Daftar Kategori</h1>

@if (session('status'))
    <div class="bg-green-50 text-green-700 p-3 rounded-md mb-4">
        {{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div class="bg-red-50 text-red-700 p-3 rounded-md mb-4">
        {{ session('error') }}
    </div>
@endif

@php
    $sortLink = function (string $column) use ($q, $sort, $direction) {
        $next = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';

        return route('categories.index', array_filter([
            'q' => $q,
            'sort' => $column,
            'direction' => $next,
        ], fn ($value) => $value !== ''));
    };
@endphp

<form method="GET" action="{{ route('categories.index') }}" class="flex gap-3 items-end mb-4">
    <label class="block">
        <span class="text-sm font-medium">Cari</span>
        <input
            type="text"
            name="q"
            value="{{ $q }}"
            placeholder="Nama kategori"
            class="mt-1 block border rounded-md px-3 py-2 text-sm"
        >
    </label>

    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="direction" value="{{ $direction }}">

    <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-md text-sm">Cari</button>

    @if ($q !== '')
        <a href="{{ route('categories.index') }}" class="text-sm underline">Reset</a>
    @endif
</form>

<form method="POST" action="{{ route('categories.store') }}" class="flex gap-3 items-end mb-6">
    @csrf

    <label class="block">
        <span class="text-sm font-medium">Nama Kategori</span>
        <input
            type="text"
            name="name"
            value="{{ old('name') }}"
            class="mt-1 block border rounded-md px-3 py-2 text-sm"
        >

        @error('name')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm">Tambah Kategori</button>
</form>

<form id="bulk-delete-form" method="POST" action="{{ route('categories.bulk-delete') }}">
    @csrf
    @method('DELETE')
</form>

<div class="flex gap-3 items-center mb-2">
    <button type="submit" form="bulk-delete-form" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm">
        Hapus Terpilih
    </button>
    <span class="text-sm text-slate-500">Kategori yang masih memiliki produk akan dilewati.</span>
</div>

<table class="w-full text-left border-collapse" x-data="{ editing: null }">
    <thead>
        <tr class="border-b">
            <th class="py-2 pr-4 w-8"></th>
            <th class="py-2 pr-4">
                <a href="{{ $sortLink('name') }}" class="hover:underline">
                    Nama @if ($sort === 'name') {{ $direction === 'asc' ? '↑' : '↓' }} @endif
                </a>
            </th>
            <th class="py-2 pr-4">
                <a href="{{ $sortLink('products_count') }}" class="hover:underline">
                    Jumlah Produk @if ($sort === 'products_count') {{ $direction === 'asc' ? '↑' : '↓' }} @endif
                </a>
            </th>
            <th class="py-2 pr-4">Aksi</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($categories as $category)
            <tr class="border-b">
                <td class="py-2 pr-4">
                    <input type="checkbox" name="ids[]" value="{{ $category->id }}" form="bulk-delete-form">
                </td>
                <td class="py-2 pr-4">{{ $category->name }}</td>
                <td class="py-2 pr-4">{{ $category->products_count }}</td>
                <td class="py-2 pr-4">
                    <button
                        type="button"
                        x-on:click="editing = editing === {{ $category->id }} ? null : {{ $category->id }}"
                        class="bg-amber-500 text-white px-3 py-1 rounded-md"
                    >
                        Ubah
                    </button>

                    <form method="POST" action="{{ route('categories.destroy', $category) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded-md">Hapus</button>
                    </form>
                </td>
            </tr>

            <tr x-show="editing === {{ $category->id }}" style="display: none" class="border-b bg-slate-50">
                <td colspan="4" class="py-2 pr-4">
                    <form
                        method="POST"
                        action="{{ route('categories.update', $category) }}"
                        class="flex gap-3 items-end"
                    >
                        @csrf
                        @method('PUT')

                        <label class="block">
                            <span class="text-sm font-medium">Nama Kategori</span>
                            <input
                                type="text"
                                name="name"
                                value="{{ $category->name }}"
                                class="mt-1 block border rounded-md px-3 py-2 text-sm"
                            >
                        </label>

                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm">Simpan</button>
                        <button
                            type="button"
                            x-on:click="editing = null"
                            class="text-sm underline"
                        >
                            Batal
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="py-4 text-slate-500">Belum ada kategori.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="mt-4">
    {{ $categories->links() }}
</div>
@endsection
