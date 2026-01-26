<?php

declare(strict_types=1);

namespace App\Exports\Catalog;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class PricebookExport implements WithMultipleSheets
{
    /**
     * @param array<int, array{name: string, rows: array}> $sheets
     */
    public function __construct(private readonly array $sheets)
    {
    }

    public function sheets(): array
    {
        $exports = [];
        foreach ($this->sheets as $sheet) {
            $exports[] = new PricebookSheetExport(
                $sheet['name'],
                $sheet['rows'] ?? []
            );
        }

        return $exports;
    }
}
