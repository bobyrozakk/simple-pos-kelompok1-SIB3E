<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_masih_berlaku_bisa_akses_produk(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('uji', ['products:read'], now()->addHour())->plainTextToken;

        $this->withToken($token)->getJson('/api/products')->assertOk(); // 200
    }

    public function test_token_kedaluwarsa_ditolak(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('uji', ['products:read'], now()->addHour())->plainTextToken;

        Carbon::setTestNow(now()->addHours(2));

        $this->withToken($token)->getJson('/api/products')->assertUnauthorized(); // 401

        Carbon::setTestNow();
    }
}
