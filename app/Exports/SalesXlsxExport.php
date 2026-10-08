<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesXlsxExport extends DefaultValueBinder implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithColumnFormatting,
    WithStyles,
    WithEvents,
    WithCustomValueBinder,
    WithChunkReading,
    ShouldAutoSize
{
    private int $sequence = 1;

    private int $rowCount;

    private array $totals;

    public function __construct(private readonly Builder $builder)
    {
        $query = clone $builder;
        $this->rowCount = (clone $query)->count();
        $this->totals = [
            (int) (clone $query)->sum('selling_price'),
            (int) (clone $query)->sum('cost_price'),
            (int) (clone $query)->sum('profit'),
        ];
    }

    public function query(): Builder
    {
        return (clone $this->builder)->reorder()->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'No', 'Tanggal', 'Penjual', 'Pembeli', 'No HP', 'Model', 'Kapasitas',
            'Warna', 'Kondisi', 'IMEI', 'Harga Jual', 'Modal', 'Keuntungan',
            'Metode Bayar', 'Catatan',
        ];
    }

    public function map($sale): array
    {
        return [
            $this->sequence++,
            Date::dateTimeToExcel($sale->sale_date->startOfDay()),
            $sale->seller_name,
            $sale->buyer_name,
            $sale->buyer_phone,
            $sale->model,
            $sale->storage,
            $sale->color,
            $sale->condition === Sale::CONDITION_NEW ? 'Baru' : 'Second',
            (string) $sale->imei,
            (int) $sale->selling_price,
            (int) $sale->cost_price,
            (int) $sale->profit,
            [
                Sale::PAYMENT_TRANSFER => 'Transfer',
                Sale::PAYMENT_CASH => 'Tunai',
                Sale::PAYMENT_INSTALLMENT => 'Cicilan',
                Sale::PAYMENT_OTHER => 'Lainnya',
            ][$sale->payment_method],
            $sale->notes,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => 'dd/mm/yyyy',
            'K' => '#,##0;[Red]-#,##0',
            'L' => '#,##0;[Red]-#,##0',
            'M' => '#,##0;[Red]-#,##0',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getRow() > 1 && Coordinate::columnIndexFromString($cell->getColumn()) === 10) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->rowCount + 2;

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:O'.max(1, $this->rowCount + 1));
                $sheet->setCellValue("A{$lastRow}", 'TOTAL');

                foreach (['K', 'L', 'M'] as $index => $column) {
                    $sheet->setCellValue("{$column}{$lastRow}", $this->totals[$index]);
                }

                $sheet->getStyle("A{$lastRow}:O{$lastRow}")->getFont()->setBold(true);
            },
        ];
    }
}
