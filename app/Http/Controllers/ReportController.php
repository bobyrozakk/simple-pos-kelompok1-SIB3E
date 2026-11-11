<?php

namespace App\Http\Controllers;

use App\Exports\SalesReportExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->resolveDateRange($request);
        $categoryId = $request->query('category_id');
        $query = $this->filteredQuery($from, $to, $categoryId);
        $categories = Category::orderBy('name')->get();

        return view('reports.index', [
            'totalPenjualan' => $query->sum('total'),
            'jumlahTransaksi' => $query->count(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'categories' => $categories,
            'categoryId' => $categoryId,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveDateRange($request);
        $categoryId = $request->query('category_id');
        $query = $this->filteredQuery($from, $to, $categoryId);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Tanggal', 'Kasir', 'Total']);

            foreach ($query->lazyById() as $t) {
                fputcsv($out, [$t->id, $t->created_at, $t->user->name, $t->total]);
            }

            fclose($out);
        }, 'laporan-penjualan.csv');
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->resolveDateRange($request);
        $categoryId = $request->query('category_id');

        return Excel::download(
            new SalesReportExport($this->filteredQuery($from, $to, $categoryId)),
            'laporan-penjualan.xlsx',
        );
    }

    public function exportPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveDateRange($request);
        $categoryId = $request->query('category_id');
        $query = $this->filteredQuery($from, $to, $categoryId);
        $selectedCategory = $categoryId ? Category::find($categoryId) : null;

        $pdf = Pdf::loadView('reports.pdf', [
            'totalPenjualan' => (clone $query)->sum('total'),
            'jumlahTransaksi' => (clone $query)->count(),
            'transactions' => (clone $query)->latest()->limit(50)->get(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'selectedCategory' => $selectedCategory,
        ]);

        return $pdf->download('laporan-penjualan.pdf');
    }

    public function importForm(): View
    {
        return view('reports.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $lines = file($request->file('csv')->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $rows = array_map('str_getcsv', $lines);
        $header = array_shift($rows);

        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            if (count($row) !== count($header)) {
                continue;
            }

            $validator = Validator::make(array_combine($header, $row), [
                'name' => ['required', 'string'],
                'category_id' => ['required', 'exists:categories,id'],
                'price' => ['required', 'integer', 'min:0'],
            ]);

            if ($validator->fails()) {
                continue;
            }

            $data = $validator->validated();

            $product = Product::updateOrCreate(
                ['name' => $data['name'], 'category_id' => $data['category_id']],
                ['price' => $data['price']],
            );

            $product->wasRecentlyCreated ? $created++ : $updated++;
        }

        return back()->with('success', "{$created} produk baru, {$updated} diperbarui.");
    }

    private function resolveDateRange(Request $request): array
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        return [$from, $to];
    }

    private function filteredQuery($from, $to, $categoryId = null): Builder
    {
        return Transaction::with('user')
            ->whereBetween('created_at', [$from, $to])
            ->when($categoryId, function (Builder $query, $categoryId) {
                $query->whereHas('details.product', function (Builder $q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            });
    }
}