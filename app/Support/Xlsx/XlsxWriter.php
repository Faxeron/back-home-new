<?php

declare(strict_types=1);

namespace App\Support\Xlsx;

use RuntimeException;
use ZipArchive;

final class XlsxWriter
{
    public function write(string $filePath, array $sheets): void
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Unable to create xlsx file: {$filePath}");
        }

        $sharedStrings = [];
        $sharedIndex = [];
        foreach ($sheets as $sheet) {
            $rows = $sheet['rows'] ?? [];
            foreach ($rows as $row) {
                foreach ($row as $value) {
                    if ($value === null || is_numeric($value)) {
                        continue;
                    }
                    $text = (string) $value;
                    if (!array_key_exists($text, $sharedIndex)) {
                        $sharedIndex[$text] = count($sharedStrings);
                        $sharedStrings[] = $text;
                    }
                }
            }
        }

        $sheetFiles = [];
        $sheetIndex = 1;

        foreach ($sheets as $sheet) {
            $name = $sheet['name'] ?? null;
            $rows = $sheet['rows'] ?? [];
            if (!$name) {
                throw new RuntimeException('Sheet name is required.');
            }
            $sheetXml = $this->buildSheetXml($rows, $sharedIndex);
            $sheetPath = "xl/worksheets/sheet{$sheetIndex}.xml";
            $zip->addFromString($sheetPath, $sheetXml);
            $sheetFiles[] = [
                'name' => $name,
                'path' => "worksheets/sheet{$sheetIndex}.xml",
            ];
            $sheetIndex++;
        }

        $zip->addFromString('[Content_Types].xml', $this->buildContentTypesXml($sheetFiles));
        $zip->addFromString('_rels/.rels', $this->buildRootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->buildWorkbookXml($sheetFiles));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->buildWorkbookRelsXml($sheetFiles));
        $zip->addFromString('xl/styles.xml', $this->buildStylesXml());
        $zip->addFromString('xl/sharedStrings.xml', $this->buildSharedStringsXml($sharedStrings));
        $zip->addFromString('xl/theme/theme1.xml', $this->buildThemeXml());
        $zip->addFromString('docProps/core.xml', $this->buildDocPropsCoreXml());
        $zip->addFromString('docProps/app.xml', $this->buildDocPropsAppXml());

        $zip->close();
    }

    private function buildSheetXml(array $rows, array $sharedIndex): string
    {
        $rowXml = [];
        $rowIndex = 1;

        foreach ($rows as $row) {
            $cellXml = [];
            $colIndex = 0;
            foreach ($row as $value) {
                $col = $this->indexToColumn($colIndex);
                $ref = $col . $rowIndex;
                if ($value === null) {
                    $colIndex++;
                    continue;
                }
                if (is_numeric($value)) {
                    $cellXml[] = '<c r="' . $ref . '"><v>' . $this->escape((string) $value) . '</v></c>';
                } else {
                    $index = $sharedIndex[(string) $value] ?? 0;
                    $cellXml[] = '<c r="' . $ref . '" t="s"><v>' . $index . '</v></c>';
                }
                $colIndex++;
            }

            $rowXml[] = '<row r="' . $rowIndex . '">' . implode('', $cellXml) . '</row>';
            $rowIndex++;
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . implode('', $rowXml) . '</sheetData>'
            . '</worksheet>';
    }

    private function buildWorkbookXml(array $sheetFiles): string
    {
        $sheetsXml = [];
        $sheetId = 1;
        foreach ($sheetFiles as $sheet) {
            $sheetsXml[] = '<sheet name="' . $this->escape($sheet['name']) . '" sheetId="' . $sheetId . '" r:id="rId' . $sheetId . '"/>';
            $sheetId++;
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . implode('', $sheetsXml) . '</sheets>'
            . '</workbook>';
    }

    private function buildWorkbookRelsXml(array $sheetFiles): string
    {
        $rels = [];
        $sheetId = 1;
        foreach ($sheetFiles as $sheet) {
            $rels[] = '<Relationship Id="rId' . $sheetId . '" '
                . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" '
                . 'Target="' . $sheet['path'] . '"/>';
            $sheetId++;
        }

        $rels[] = '<Relationship Id="rId' . $sheetId . '" '
            . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" '
            . 'Target="styles.xml"/>';
        $sheetId++;
        $rels[] = '<Relationship Id="rId' . $sheetId . '" '
            . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" '
            . 'Target="sharedStrings.xml"/>';
        $sheetId++;
        $rels[] = '<Relationship Id="rId' . $sheetId . '" '
            . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" '
            . 'Target="theme/theme1.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . implode('', $rels)
            . '</Relationships>';
    }

    private function buildRootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" '
            . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" '
            . 'Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" '
            . 'Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" '
            . 'Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" '
            . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" '
            . 'Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function buildContentTypesXml(array $sheetFiles): string
    {
        $overrides = [
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>',
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>',
            '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>',
            '<Override PartName="/xl/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>',
            '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>',
            '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>',
        ];
        $sheetIndex = 1;
        foreach ($sheetFiles as $sheet) {
            $overrides[] = '<Override PartName="/xl/worksheets/sheet' . $sheetIndex . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $sheetIndex++;
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . implode('', $overrides)
            . '</Types>';
    }

    private function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function buildSharedStringsXml(array $strings): string
    {
        $items = [];
        foreach ($strings as $value) {
            $text = (string) $value;
            $preserve = (trim($text) !== $text || str_contains($text, "\n"));
            $attr = $preserve ? ' xml:space="preserve"' : '';
            $items[] = '<si><t' . $attr . '>' . $this->escape($text) . '</t></si>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'count="' . count($strings) . '" uniqueCount="' . count($strings) . '">'
            . implode('', $items)
            . '</sst>';
    }

    private function buildThemeXml(): string
    {
        return '<?xml version="1.0"?>'
            . '<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Office Theme">'
            . '<a:themeElements>'
            . '<a:clrScheme name="Office">'
            . '<a:dk1><a:sysClr val="windowText" lastClr="000000"/></a:dk1>'
            . '<a:lt1><a:sysClr val="window" lastClr="FFFFFF"/></a:lt1>'
            . '<a:dk2><a:srgbClr val="1F497D"/></a:dk2>'
            . '<a:lt2><a:srgbClr val="EEECE1"/></a:lt2>'
            . '<a:accent1><a:srgbClr val="4F81BD"/></a:accent1>'
            . '<a:accent2><a:srgbClr val="C0504D"/></a:accent2>'
            . '<a:accent3><a:srgbClr val="9BBB59"/></a:accent3>'
            . '<a:accent4><a:srgbClr val="8064A2"/></a:accent4>'
            . '<a:accent5><a:srgbClr val="4BACC6"/></a:accent5>'
            . '<a:accent6><a:srgbClr val="F79646"/></a:accent6>'
            . '<a:hlink><a:srgbClr val="0000FF"/></a:hlink>'
            . '<a:folHlink><a:srgbClr val="800080"/></a:folHlink>'
            . '</a:clrScheme>'
            . '<a:fontScheme name="Office">'
            . '<a:majorFont>'
            . '<a:latin typeface="Cambria"/>'
            . '<a:ea typeface=""/>'
            . '<a:cs typeface=""/>'
            . '<a:font script="Jpan" typeface="&#xFF2D;&#xFF33; &#xFF30;&#x30B4;&#x30B7;&#x30C3;&#x30AF;"/>'
            . '<a:font script="Hang" typeface="&#xB9D1;&#xC740; &#xACE0;&#xB515;"/>'
            . '<a:font script="Hans" typeface="&#x5B8B;&#x4F53;"/>'
            . '<a:font script="Hant" typeface="&#x65B0;&#x7D30;&#x660E;&#x9AD4;"/>'
            . '<a:font script="Arab" typeface="Times New Roman"/>'
            . '<a:font script="Hebr" typeface="Times New Roman"/>'
            . '<a:font script="Thai" typeface="Tahoma"/>'
            . '<a:font script="Ethi" typeface="Nyala"/>'
            . '<a:font script="Beng" typeface="Vrinda"/>'
            . '<a:font script="Gujr" typeface="Shruti"/>'
            . '<a:font script="Khmr" typeface="MoolBoran"/>'
            . '<a:font script="Knda" typeface="Tunga"/>'
            . '<a:font script="Guru" typeface="Raavi"/>'
            . '<a:font script="Cans" typeface="Euphemia"/>'
            . '<a:font script="Cher" typeface="Plantagenet Cherokee"/>'
            . '<a:font script="Yiii" typeface="Microsoft Yi Baiti"/>'
            . '<a:font script="Tibt" typeface="Microsoft Himalaya"/>'
            . '<a:font script="Thaa" typeface="MV Boli"/>'
            . '<a:font script="Deva" typeface="Mangal"/>'
            . '<a:font script="Telu" typeface="Gautami"/>'
            . '<a:font script="Taml" typeface="Latha"/>'
            . '<a:font script="Syrc" typeface="Estrangelo Edessa"/>'
            . '<a:font script="Orya" typeface="Kalinga"/>'
            . '<a:font script="Mlym" typeface="Kartika"/>'
            . '<a:font script="Laoo" typeface="DokChampa"/>'
            . '<a:font script="Sinh" typeface="Iskoola Pota"/>'
            . '<a:font script="Mong" typeface="Mongolian Baiti"/>'
            . '<a:font script="Viet" typeface="Times New Roman"/>'
            . '<a:font script="Uigh" typeface="Microsoft Uighur"/>'
            . '</a:majorFont>'
            . '<a:minorFont>'
            . '<a:latin typeface="Calibri"/>'
            . '<a:ea typeface=""/>'
            . '<a:cs typeface=""/>'
            . '<a:font script="Jpan" typeface="&#xFF2D;&#xFF33; &#xFF30;&#x30B4;&#x30B7;&#x30C3;&#x30AF;"/>'
            . '<a:font script="Hang" typeface="&#xB9D1;&#xC740; &#xACE0;&#xB515;"/>'
            . '<a:font script="Hans" typeface="&#x5B8B;&#x4F53;"/>'
            . '<a:font script="Hant" typeface="&#x65B0;&#x7D30;&#x660E;&#x9AD4;"/>'
            . '<a:font script="Arab" typeface="Arial"/>'
            . '<a:font script="Hebr" typeface="Arial"/>'
            . '<a:font script="Thai" typeface="Tahoma"/>'
            . '<a:font script="Ethi" typeface="Nyala"/>'
            . '<a:font script="Beng" typeface="Vrinda"/>'
            . '<a:font script="Gujr" typeface="Shruti"/>'
            . '<a:font script="Khmr" typeface="DaunPenh"/>'
            . '<a:font script="Knda" typeface="Tunga"/>'
            . '<a:font script="Guru" typeface="Raavi"/>'
            . '<a:font script="Cans" typeface="Euphemia"/>'
            . '<a:font script="Cher" typeface="Plantagenet Cherokee"/>'
            . '<a:font script="Yiii" typeface="Microsoft Yi Baiti"/>'
            . '<a:font script="Tibt" typeface="Microsoft Himalaya"/>'
            . '<a:font script="Thaa" typeface="MV Boli"/>'
            . '<a:font script="Deva" typeface="Mangal"/>'
            . '<a:font script="Telu" typeface="Gautami"/>'
            . '<a:font script="Taml" typeface="Latha"/>'
            . '<a:font script="Syrc" typeface="Estrangelo Edessa"/>'
            . '<a:font script="Orya" typeface="Kalinga"/>'
            . '<a:font script="Mlym" typeface="Kartika"/>'
            . '<a:font script="Laoo" typeface="DokChampa"/>'
            . '<a:font script="Sinh" typeface="Iskoola Pota"/>'
            . '<a:font script="Mong" typeface="Mongolian Baiti"/>'
            . '<a:font script="Viet" typeface="Arial"/>'
            . '<a:font script="Uigh" typeface="Microsoft Uighur"/>'
            . '</a:minorFont>'
            . '</a:fontScheme>'
            . '<a:fmtScheme name="Office">'
            . '<a:fillStyleLst>'
            . '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'
            . '<a:gradFill rotWithShape="1"><a:gsLst>'
            . '<a:gs pos="0"><a:schemeClr val="phClr"><a:tint val="50000"/><a:satMod val="300000"/></a:schemeClr></a:gs>'
            . '<a:gs pos="35000"><a:schemeClr val="phClr"><a:tint val="37000"/><a:satMod val="300000"/></a:schemeClr></a:gs>'
            . '<a:gs pos="100000"><a:schemeClr val="phClr"><a:tint val="15000"/><a:satMod val="350000"/></a:schemeClr></a:gs>'
            . '</a:gsLst><a:lin ang="16200000" scaled="1"/></a:gradFill>'
            . '<a:gradFill rotWithShape="1"><a:gsLst>'
            . '<a:gs pos="0"><a:schemeClr val="phClr"><a:shade val="51000"/><a:satMod val="130000"/></a:schemeClr></a:gs>'
            . '<a:gs pos="80000"><a:schemeClr val="phClr"><a:shade val="93000"/><a:satMod val="130000"/></a:schemeClr></a:gs>'
            . '<a:gs pos="100000"><a:schemeClr val="phClr"><a:shade val="94000"/><a:satMod val="135000"/></a:schemeClr></a:gs>'
            . '</a:gsLst><a:lin ang="16200000" scaled="0"/></a:gradFill>'
            . '</a:fillStyleLst>'
            . '<a:lnStyleLst>'
            . '<a:ln w="9525" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"><a:shade val="95000"/><a:satMod val="105000"/></a:schemeClr></a:solidFill><a:prstDash val="solid"/></a:ln>'
            . '<a:ln w="25400" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln>'
            . '<a:ln w="38100" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln>'
            . '</a:lnStyleLst>'
            . '<a:effectStyleLst>'
            . '<a:effectStyle><a:effectLst><a:outerShdw blurRad="40000" dist="20000" dir="5400000" rotWithShape="0"><a:srgbClr val="000000"><a:alpha val="38000"/></a:srgbClr></a:outerShdw></a:effectLst></a:effectStyle>'
            . '<a:effectStyle><a:effectLst><a:outerShdw blurRad="40000" dist="23000" dir="5400000" rotWithShape="0"><a:srgbClr val="000000"><a:alpha val="35000"/></a:srgbClr></a:outerShdw></a:effectLst></a:effectStyle>'
            . '<a:effectStyle><a:effectLst><a:outerShdw blurRad="40000" dist="23000" dir="5400000" rotWithShape="0"><a:srgbClr val="000000"><a:alpha val="35000"/></a:srgbClr></a:outerShdw></a:effectLst>'
            . '<a:scene3d><a:camera prst="orthographicFront"><a:rot lat="0" lon="0" rev="0"/></a:camera><a:lightRig rig="threePt" dir="t"><a:rot lat="0" lon="0" rev="1200000"/></a:lightRig></a:scene3d>'
            . '<a:sp3d><a:bevelT w="63500" h="25400"/></a:sp3d>'
            . '</a:effectStyle>'
            . '</a:effectStyleLst>'
            . '<a:bgFillStyleLst>'
            . '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'
            . '<a:gradFill rotWithShape="1"><a:gsLst>'
            . '<a:gs pos="0"><a:schemeClr val="phClr"><a:tint val="40000"/><a:satMod val="350000"/></a:schemeClr></a:gs>'
            . '<a:gs pos="40000"><a:schemeClr val="phClr"><a:tint val="45000"/><a:shade val="99000"/><a:satMod val="350000"/></a:schemeClr></a:gs>'
            . '<a:gs pos="100000"><a:schemeClr val="phClr"><a:shade val="20000"/><a:satMod val="255000"/></a:schemeClr></a:gs>'
            . '</a:gsLst><a:path path="circle"><a:fillToRect l="50000" t="-80000" r="50000" b="180000"/></a:path></a:gradFill>'
            . '<a:gradFill rotWithShape="1"><a:gsLst>'
            . '<a:gs pos="0"><a:schemeClr val="phClr"><a:tint val="80000"/><a:satMod val="300000"/></a:schemeClr></a:gs>'
            . '<a:gs pos="100000"><a:schemeClr val="phClr"><a:shade val="30000"/><a:satMod val="200000"/></a:schemeClr></a:gs>'
            . '</a:gsLst><a:path path="circle"><a:fillToRect l="50000" t="50000" r="50000" b="50000"/></a:path></a:gradFill>'
            . '</a:bgFillStyleLst>'
            . '</a:fmtScheme>'
            . '</a:themeElements>'
            . '<a:objectDefaults/>'
            . '<a:extraClrSchemeLst/>'
            . '</a:theme>';
    }

    private function buildDocPropsCoreXml(): string
    {
        $timestamp = now()->toAtomString();
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" '
            . 'xmlns:dcterms="http://purl.org/dc/terms/" '
            . 'xmlns:dcmitype="http://purl.org/dc/dcmitype/" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>ERP</dc:creator>'
            . '<cp:lastModifiedBy>ERP</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $this->escape($timestamp) . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $this->escape($timestamp) . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function buildDocPropsAppXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            . 'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>ERP</Application>'
            . '</Properties>';
    }

    private function indexToColumn(int $index): string
    {
        $index++;
        $letters = '';
        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $letters = chr(65 + $remainder) . $letters;
            $index = intdiv($index - 1, 26);
        }
        return $letters;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
