<?php

namespace App\Imports;

use App\Models\ImportError;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class ProductsImport implements ToModel, WithHeadingRow, WithChunkReading, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    public function __construct(private string $batchId) {}

    public function model(array $row): ?Model
    {
        Product::updateOrCreate(
            ['name' => $row['name'], 'category_id' => $row['category_id']],
            ['price' => $row['price']],
        );

        return null; // sudah disimpan di atas, jangan di-insert lagi oleh library
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:0'],
        ];
    }

    public function onFailure(Failure ...$failures): void
    {
        $records = collect($failures)
            ->groupBy(fn (Failure $f) => $f->row())
            ->map(fn ($group, $row) => [
                'batch_id' => $this->batchId,
                'row_number' => $row,
                'row_data' => implode(',', array_map('strval', $group->first()->values())),
                'message' => $group->flatMap(fn (Failure $f) => $f->errors())->implode(' '),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        ImportError::insert($records);
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
