@extends('layouts.app')
@section('title', 'Kasir')
@section('content')
    <h1 class="text-lg font-semibold mb-4">Transaksi Kasir</h1>
    <div x-data="{
        cart: [],
        selectedId: null,
        addToCart(id, name, price) {
            this.cart.push({ id, name, price });
            this.selectedId = id;
        },
        removeFromCart(id) {
            this.cart = this.cart.filter(item => item.id !== id);
        },
        subtotal() {
            return this.cart.reduce((sum, item) => sum + item.price, 0);
        }
    }">
        <div class="grid grid-cols-3 gap-4">
            @foreach ($products as $product)
                <div class="border rounded-md p-3 cursor-pointer transition-all"
                    :class="{ 'ring-2 ring-blue-500': selectedId === {{ $product->id }} }"
                    @click="addToCart({{ $product->id }}, '{{ $product->name }}', {{ $product->price }})">
                    <div class="flex items-center gap-2">
                        <p class="font-medium">{{ $product->name }}</p>
                        @if ($product->stock < 10)
                            <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded">Stok Menipis</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-500">Rp {{ number_format($product->price) }}</p>
                </div>
            @endforeach
        </div>
        <div class="mt-4">
            {{ $products->links() }}
        </div>
        <div class="mt-4 border-t pt-3">
            <template x-for="item in cart" :key="item.id">
                <div class="flex justify-between items-center mb-2">
                    <p x-text="item.name + ' - Rp ' + item.price"></p>
                    <button @click="removeFromCart(item.id)" class="text-red-600 border border-red-600 px-2 py-1 rounded text-sm">Hapus</button>
                </div>
            </template>
            <p class="font-semibold mt-2">Subtotal: Rp <span x-text="subtotal()"></span></p>
        </div>
    </div>
@endsection
