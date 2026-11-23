<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ApiClientDemo extends Command
{
    protected $signature = 'api:demo';

    protected $description = 'Demo penggunaan API Simple POS melalui HTTP';

    public function handle(): int
    {
        $baseUrl = 'http://127.0.0.1:8000';

        try {
            $login = Http::acceptJson()->post("{$baseUrl}/api/login", [
                'email' => 'kasir@pos.test',
                'password' => 'password',
            ]);

            $token = $login->json('token');

            if (! $login->successful() || ! is_string($token) || $token === '') {
                $message = $login->json('message', 'Token tidak ditemukan.');
                $this->error("Login gagal ({$login->status()}): {$message}");

                return self::FAILURE;
            }

            $this->info('Login berhasil, token diterima.');

            $products = Http::acceptJson()
                ->withToken($token)
                ->get("{$baseUrl}/api/products");

            if (! $products->successful()) {
                $message = $products->json('message', 'Gagal mengambil produk.');
                $this->error("Gagal mengambil produk ({$products->status()}): {$message}");

                return self::FAILURE;
            }

            $items = $products->json('data', []);
            $this->info('Jumlah produk: '.count($items));

            $productId = $items[0]['id'] ?? null;

            if ($productId === null) {
                $this->error('Daftar produk kosong. Tambahkan minimal satu produk.');

                return self::FAILURE;
            }

            $transaction = Http::acceptJson()
                ->withToken($token)
                ->post("{$baseUrl}/api/transactions", [
                    'items' => [
                        ['product_id' => $productId, 'qty' => 1],
                    ],
                ]);

            if ($transaction->status() === 201) {
                $this->info('Transaksi tersimpan: '.json_encode($transaction->json('data')));

                return self::SUCCESS;
            }

            $message = $transaction->json('message', 'Terjadi kesalahan.');

            match ($transaction->status()) {
                401 => $this->error('Token tidak valid. Jalankan ulang demo untuk login kembali.'),
                422 => $this->error("Input ditolak: {$message}"),
                default => $this->error("Transaksi gagal ({$transaction->status()}): {$message}"),
            };

            return self::FAILURE;
        } catch (ConnectionException) {
            $this->error("Server tidak bisa dihubungi di {$baseUrl}. Pastikan Artisan serve berjalan.");

            return self::FAILURE;
        }
    }
}