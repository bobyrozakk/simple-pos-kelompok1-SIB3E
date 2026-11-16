<?php

namespace App\Jobs;

use App\Imports\ProductsImport;
use App\Models\ImportError;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;

class ProcessProductImportExcel implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [10, 60];
    public int $timeout = 60;

    public function __construct(private string $csvPath, private string $batchId) {}

    public function handle(): void
    {
        ImportError::where('batch_id', $this->batchId)->delete();

        Excel::import(new ProductsImport($this->batchId), $this->csvPath, 'local', ExcelType::CSV);

        Storage::delete($this->csvPath);
    }
}
