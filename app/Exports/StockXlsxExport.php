<?php

namespace App\Exports;

use App\Models\Stock;
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

class StockXlsxExport extends DefaultValueBinder implements
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

    private int $totalCost;

    public function __construct(private readonly Builder $builder)
    {
        $this->rowCount = (clone $builder)->count();
        $this->totalCost = (int) (clone $builder)->sum('cost_price');
    }

    public function query(): Builder
    {
        return (clone $this->builder)->reorder()->orderBy('id');
    }

    public function headings(): array
    {
        return ['No', 'Model', 'Kapasitas', 'Warna', 'Kondisi', 'IMEI', 'Status', 'Tanggal masuk', 'Umur (hari)', 'Modal', 'Varian', 'Baterai'];
    }

    public function map($stock): array
    {
        $age = $stock->purchase_date->diffInDays(now()->startOfDay());

        return [
            $this->sequence++,
            $stock->phoneModel?->name ?? '',
            $stock->storage,
            $stock->color,
            $stock->condition === 'new' ? 'Baru' : 'Second',
            (string) $stock->imei,
            $stock->status === Stock::STATUS_AVAILABLE ? 'Tersedia' : 'Terjual',
            Date::dateTimeToExcel($stock->purchase_date->startOfDay()),
            $age,
            (int) $stock->cost_price,
            $stock->variant ?? '',
            $stock->battery_health,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => 'dd/mm/yyyy',
            'J' => '#,##0;[Red]-#,##0',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getRow() > 1 && Coordinate::columnIndexFromString($cell->getColumn()) === 6) {
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
                $totalRow = $this->rowCount + 2;
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:L'.max(1, $this->rowCount + 1));
                $sheet->setCellValue("A{$totalRow}", 'TOTAL');
                $sheet->setCellValue("J{$totalRow}", $this->totalCost);
                $sheet->getStyle("A{$totalRow}:L{$totalRow}")->getFont()->setBold(true);
            },
        ];
    }
}
