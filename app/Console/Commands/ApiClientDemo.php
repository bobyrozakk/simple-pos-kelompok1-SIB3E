<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ApiClientDemo extends Command
{
    protected $signature = 'api:demo';

    protected $description = 'Demo penggunaan API Simple POS melalui HTTP';

    public function handle(): int
    {
        $baseUrl = 'http://127.0.0.1:8000';

        // klien yang baik mengulang hanya error yang memang layak diulang, dengan jeda yang makin panjang
        $when = fn ($e) => $e instanceof ConnectionException
            || in_array($e->response?->status(), [429, 502, 503, 504]);
        $backoff = fn (int $attempt) => $attempt * 1000;

        try {
            $login = Http::acceptJson()->post("{$baseUrl}/api/v1/login", [
                'email' => 'kasir@pos.test',
                'password' => 'password',
            ]);

            $token = $login->json('token');

            if (! $login->successful() || ! is_string($token) || $token === '') {
                $message = $login->json('message', 'Token tidak ditemukan.');
                $this->error("Login gagal ({$login->status()}): {$message}");

                return self::FAILURE;
            }

            $this->info("Login berhasil, token: {$token}");

            $products = Http::acceptJson()
                ->withToken($token)
                ->retry(3, $backoff, $when, throw: false)
                ->get("{$baseUrl}/api/v1/products");

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

            $payload = [
                'items' => [
                    ['product_id' => $productId, 'qty' => 1],
                ],
            ];
            $key = (string) Str::uuid();

            $transaction = Http::acceptJson()
                ->withToken($token)
                ->withHeaders(['Idempotency-Key' => $key])
                ->retry(3, $backoff, $when, throw: false)
                ->post("{$baseUrl}/api/v1/transactions", $payload);

            if ($transaction->status() === 201) {
                $this->info('Transaksi tersimpan: '.json_encode($transaction->json('data')));

                // simulasi retry setelah timeout: request yang sama dengan key yang sama
                $retry = Http::acceptJson()
                    ->withToken($token)
                    ->withHeaders(['Idempotency-Key' => $key])
                    ->retry(3, $backoff, $when, throw: false)
                    ->post("{$baseUrl}/api/v1/transactions", $payload);

                $this->info('Ulang dengan key yang sama: '.$retry->status().', replayed = '.$retry->header('Idempotent-Replayed'));

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
