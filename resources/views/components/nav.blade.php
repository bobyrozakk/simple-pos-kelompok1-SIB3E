<nav class="bg-slate-900 text-white px-6 py-3 flex items-center space-x-6">
    <span class="font-semibold text-lg tracking-wide mr-2">Simple POS</span>
    
    <a href="{{ route('pos.create') }}" 
       class="{{ request()->routeIs('pos.create') ? 'bg-slate-800 text-blue-400 font-medium px-3 py-1.5 rounded-md shadow-sm' : 'text-slate-300 hover:text-white transition' }}">
       Kasir
    </a>
    
    <a href="{{ route('transactions.index') }}" 
       class="{{ request()->routeIs('transactions.*') || request()->routeIs('transactions.index') ? 'bg-slate-800 text-blue-400 font-medium px-3 py-1.5 rounded-md shadow-sm' : 'text-slate-300 hover:text-white transition' }}">
       Transaksi
    </a>

    <a href="{{ route('products.index') }}" class="hover:underline">Produk</a>
</nav>