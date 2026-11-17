<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_pdf_report_with_top_products(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_test@pos.test'],
            ['name' => 'Admin Test', 'role' => 'admin', 'password' => 'password']
        );

        $category = Category::firstOrCreate(['name' => 'Makanan Test']);
        $product = Product::firstOrCreate(
            ['name' => 'Kopi Mantap Test'],
            ['category_id' => $category->id, 'price' => 15000, 'stock' => 50]
        );

        $transaction = Transaction::create([
            'user_id' => $admin->id,
            'total' => 30000,
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'qty' => 2,
            'subtotal' => 30000,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.export.pdf', [
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->endOfDay()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }
}
