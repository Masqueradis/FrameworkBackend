<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\ProductRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $reportId
    ) {
        $this->onConnection('rabbitmq');
        $this->onQueue('reports_queue');
    }

    public function handle(
        OrderRepositoryInterface $orderRepository,
        ProductRepository $productRepository,
    ): void {
        $report = Report::findOrFail($this->reportId);

        try {
            $report->update(['status' => ReportStatus::Processing]);
            $csvContent = $this->generateCsvContent($report, $orderRepository, $productRepository);

            $fileName = "reports/{$report->type}_" . now()->format('Y_m_d_H_i_s') . "_{$report->id}.csv";
            Storage::disk('minio')->put($fileName, $csvContent);

            $report->update([
                'status' => ReportStatus::Completed,
                'file_path' => $fileName
            ]);
        } catch (\Throwable $exception) {
            $report->update(['status' => ReportStatus::Failed]);
            throw $exception;
        }
    }

    private function generateCsvContent(
        Report $report,
        OrderRepositoryInterface $orderRepository,
        ProductRepository $productRepository
    ): string {
        $handle = fopen('php://temp', 'r+');

        if ($report->type === 'sales') {
            fputcsv($handle, [
                'Order ID',
                'Customer Name',
                'Email',
                'Total (Cents)',
                'Status',
                'Date',
                ]);

            $dateFrom = $report->filters['date_from'] ?? '2000-01-01';
            $dateTo = $report->filters['date_to'] ?? now()->toDateString();

            $orderRepository->chunkOrdersByDateRange($dateFrom, $dateTo, 100, function ($orders) use ($handle) {
                foreach ($orders as $order) {
                    fputcsv($handle, [
                        $order->id,
                        $order->customer_name,
                        $order->customer_email,
                        $order->total_amount_cents->getCents(),
                        $order->status->value ?? $order->status,
                        $order->created_at->toDateTimeString(),
                    ]);
                }
            });
            } elseif ($report->type === 'inventory') {
                fputcsv($handle, [
                    'Product ID',
                    'Name',
                    'SKU',
                    'Price',
                    'Stock',
                    'Available',
                ]);

                $productRepository->chunkAllProducts(100, function ($products) use ($handle) {
                    foreach ($products as $product) {
                        fputcsv($handle, [
                            $product->id,
                            $product->name,
                            $product->sku,
                            $product->price,
                            $product->stock,
                            $product->available ? 'Yes' : 'No',
                        ]);
                    }
                });
            } else {
            throw new \Exception("Unknown report type: {$report->type}");
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return $csvContent;
    }
}
