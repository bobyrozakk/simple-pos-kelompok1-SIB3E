<?php

namespace App\Jobs;

use App\Models\ImportError;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProcessProductImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [10, 60];
    public int $timeout = 60;

    public function __construct(private string $csvPath, private string $batchId) {}

    public function handle(): void
    {
        ImportError::where('batch_id', $this->batchId)->delete();

        $lines = file(Storage::path($this->csvPath), FILE_IGNORE_NEW_LINES);
        $header = str_getcsv(array_shift($lines));
        $errors = [];

        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            $rowNumber = $index + 2;
            $row = str_getcsv($line);

            if (count($row) !== count($header)) {
                $errors[] = $this->errorRecord($rowNumber, $line, 'Jumlah column tidak sesuai dengan header.');
                continue;
            }

            $validator = Validator::make(array_combine($header, $row), [
                'name' => ['required', 'string'],
                'category_id' => ['required', 'exists:categories,id'],
                'price' => ['required', 'integer', 'min:0'],
            ]);

            if ($validator->fails()) {
                $errors[] = $this->errorRecord($rowNumber, $line, implode(' ', $validator->errors()->all()));
                continue;
            }

            $data = $validator->validated();

            Product::updateOrCreate(
                ['name' => $data['name'], 'category_id' => $data['category_id']],
                ['price' => $data['price']],
            );
        }

        foreach (array_chunk($errors, 100) as $chunk) {
            ImportError::insert($chunk);
        }

        Storage::delete($this->csvPath);
    }

    private function errorRecord(int $rowNumber, string $line, string $message): array
    {
        return [
            'batch_id' => $this->batchId,
            'row_number' => $rowNumber,
            'row_data' => $line,
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
