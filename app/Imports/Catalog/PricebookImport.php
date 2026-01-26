<?php

declare(strict_types=1);

namespace App\Imports\Catalog;

use App\Services\Catalog\PricebookSchema;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class PricebookImport implements WithMultipleSheets
{
    /**
     * @var array<string, array>
     */
    public array $data = [];

    public function sheets(): array
    {
        $sheets = [];
        foreach (PricebookSchema::sheetAliases() as $storeKey => $aliases) {
            foreach ($aliases as $alias) {
                $sheets[$alias] = new PricebookSheetImport($this->data, $alias, $storeKey);
            }
        }

        return $sheets;
    }
}
