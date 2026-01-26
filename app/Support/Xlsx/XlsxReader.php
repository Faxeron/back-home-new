<?php

declare(strict_types=1);

namespace App\Support\Xlsx;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

final class XlsxReader
{
    private ZipArchive $zip;
    private array $sharedStrings = [];
    private array $sheetMap = [];

    public function __construct(private readonly string $filePath)
    {
        $this->zip = new ZipArchive();
        if ($this->zip->open($filePath) !== true) {
            throw new RuntimeException("Unable to open xlsx file: {$filePath}");
        }

        $this->sharedStrings = $this->readSharedStrings();
        $this->sheetMap = $this->buildSheetMap();
    }

    public function getSheetNames(): array
    {
        return array_keys($this->sheetMap);
    }

    public function readSheet(string $sheetName): array
    {
        if (!isset($this->sheetMap[$sheetName])) {
            throw new RuntimeException("Sheet not found: {$sheetName}");
        }

        $sheetXml = $this->zip->getFromName($this->sheetMap[$sheetName]);
        if ($sheetXml === false) {
            throw new RuntimeException("Worksheet not found: {$sheetName}");
        }

        $sheet = simplexml_load_string($sheetXml);
        if (!$sheet) {
            throw new RuntimeException("Unable to parse worksheet: {$sheetName}");
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $col = preg_replace('/[^A-Z]/', '', strtoupper($ref));
                if ($col === '') {
                    continue;
                }
                $idx = self::colToIndex($col);
                $cells[$idx] = $this->readCellValue($cell);
            }
            if (!$cells) {
                continue;
            }
            $max = max(array_keys($cells));
            $rowValues = [];
            for ($i = 0; $i <= $max; $i++) {
                $rowValues[$i] = $cells[$i] ?? null;
            }
            $rows[] = $rowValues;
        }

        $headers = $rows[0] ?? [];
        $dataRows = array_slice($rows, 1);

        return [$headers, $dataRows];
    }

    public function close(): void
    {
        if ($this->zip->status === ZipArchive::ER_OK) {
            $this->zip->close();
        }
    }

    private function buildSheetMap(): array
    {
        $workbookXml = $this->zip->getFromName('xl/workbook.xml');
        $relsXml = $this->zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            throw new RuntimeException('Unable to read workbook metadata.');
        }

        $workbook = simplexml_load_string($workbookXml);
        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $rels = simplexml_load_string($relsXml);
        $rels->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $relMap = [];
        foreach ($rels->Relationship as $rel) {
            $relMap[(string) $rel['Id']] = (string) $rel['Target'];
        }

        $map = [];
        foreach ($workbook->sheets->sheet as $sheet) {
            $name = (string) $sheet['name'];
            $rid = (string) $sheet->attributes('r', true)->id;
            $target = $relMap[$rid] ?? null;
            if ($target) {
                $map[$name] = 'xl/' . ltrim($target, '/');
            }
        }

        return $map;
    }

    private function readSharedStrings(): array
    {
        $xml = $this->zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $doc = simplexml_load_string($xml);
        $doc->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $strings = [];

        foreach ($doc->si as $si) {
            $text = '';
            foreach ($si->t as $t) {
                $text .= (string) $t;
            }
            if ($text === '') {
                foreach ($si->r as $run) {
                    $text .= (string) $run->t;
                }
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function readCellValue(SimpleXMLElement $cell): ?string
    {
        $type = (string) $cell['t'];
        if ($type === 's') {
            $idx = (int) $cell->v;
            return $this->sharedStrings[$idx] ?? null;
        }
        if ($type === 'inlineStr') {
            return isset($cell->is->t) ? (string) $cell->is->t : null;
        }
        if (isset($cell->v)) {
            return (string) $cell->v;
        }
        return null;
    }

    private static function colToIndex(string $col): int
    {
        $idx = 0;
        $len = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $idx = $idx * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $idx - 1;
    }
}
