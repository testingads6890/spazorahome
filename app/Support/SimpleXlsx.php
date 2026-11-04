<?php

namespace App\Support;

use ZipArchive;

/**
 * Reads the first sheet of an .xlsx (or a .csv) and writes a plain .xlsx, with no extra packages
 * (only PHP's zip and xml, which Laravel hosting already has).
 */
class SimpleXlsx
{
    /** @return array<int, array<int, string>> rows of cell text, in sheet order (row index starts at 0) */
    public static function read(string $path, string $extension = 'xlsx'): array
    {
        if (strtolower($extension) === 'csv') {
            return self::readCsv($path);
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('This file could not be opened. Please upload the .xlsx template or a .csv file.');
        }
        try {
            $shared = [];
            if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
                foreach (self::xml($xml)->si ?? [] as $si) {
                    $text = '';
                    foreach ($si->xpath('.//*[local-name()="t"]') ?: [] as $t) {
                        $text .= (string) $t;
                    }
                    $shared[] = $text;
                }
            }
            $sheetPath = self::firstSheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                throw new \RuntimeException('The first sheet of this file is empty or unreadable.');
            }
        } finally {
            $zip->close();
        }

        $rows = [];
        foreach (self::xml($sheetXml)->sheetData->row ?? [] as $row) {
            $r = (int) $row['r'] - 1;
            foreach ($row->c as $c) {
                $col = self::columnIndex((string) $c['r']);
                $type = (string) $c['t'];
                $value = match ($type) {
                    's' => $shared[(int) $c->v] ?? '',
                    'inlineStr' => implode('', array_map('strval', $c->xpath('.//*[local-name()="t"]') ?: [])),
                    'b' => ((string) $c->v) === '1' ? 'Yes' : 'No',
                    default => (string) $c->v,
                };
                if ($type === '' || $type === 'n') {
                    $value = self::cleanNumber($value);
                }
                $rows[$r][$col] = trim($value);
            }
        }

        // Keep every row at its real position (empty rows stay empty), so row numbers match what Excel shows.
        $out = [];
        for ($r = 0, $last = $rows ? max(array_keys($rows)) : -1; $r <= $last; $r++) {
            $cells = $rows[$r] ?? [];
            $max = $cells ? max(array_keys($cells)) : -1;
            $line = [];
            for ($i = 0; $i <= $max; $i++) {
                $line[] = $cells[$i] ?? '';
            }
            $out[] = $line;
        }

        return $out;
    }

    /** Writes sheets: [name => rows]; the first row of every sheet is drawn as a header. */
    public static function write(string $path, array $sheets, array $widths = []): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $n = count($sheets);
        $types = '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $sheetTags = $rels = '';
        for ($i = 1; $i <= $n; $i++) {
            $types .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>' . $types . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F4E79"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment wrapText="1" vertical="center"/></xf></cellXfs></styleSheet>');

        $i = 0;
        foreach ($sheets as $name => $rows) {
            $i++;
            $sheetTags .= '<sheet name="' . self::esc(mb_substr($name, 0, 31)) . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
            $cols = '';
            foreach (($widths[$name] ?? []) as $idx => $w) {
                $cols .= '<col min="' . ($idx + 1) . '" max="' . ($idx + 1) . '" width="' . $w . '" customWidth="1"/>';
            }
            $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' . ($cols ? '<cols>' . $cols . '</cols>' : '') . '<sheetData>';
            foreach (array_values($rows) as $r => $cells) {
                $xml .= '<row r="' . ($r + 1) . '">';
                foreach (array_values($cells) as $c => $value) {
                    $ref = self::columnName($c) . ($r + 1);
                    // Text format for data cells, so codes like 94036000 or 007 are kept exactly.
                    $style = $r === 0 ? 2 : 1;
                    $xml .= '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . self::esc((string) $value) . '</t></is></c>';
                }
                $xml .= '</row>';
            }
            $zip->addFromString('xl/worksheets/sheet' . $i . '.xml', $xml . '</sheetData></worksheet>');
        }
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $sheetTags . '</sheets></workbook>');
        $zip->close();
    }

    private static function readCsv(string $path): array
    {
        $rows = [];
        $h = fopen($path, 'r');
        if (! $h) {
            throw new \RuntimeException('This file could not be opened.');
        }
        $first = true;
        while (($cells = fgetcsv($h, 0, ',', '"', '\\')) !== false) {
            if ($first && isset($cells[0])) {
                $cells[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $cells[0]);   // Excel's BOM
                $first = false;
            }
            $rows[] = array_map(fn ($c) => trim((string) $c), $cells);
        }
        fclose($h);

        return $rows;
    }

    private static function firstSheetPath(ZipArchive $zip): string
    {
        $wb = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wb !== false && $rels !== false) {
            $sheet = self::xml($wb)->sheets->sheet[0] ?? null;
            if ($sheet) {
                $rid = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                foreach (self::xml($rels)->Relationship as $rel) {
                    if ((string) $rel['Id'] === $rid) {
                        $target = ltrim((string) $rel['Target'], '/');

                        return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                    }
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private static function xml(string $xml): \SimpleXMLElement
    {
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_use_internal_errors($prev);
        if ($doc === false) {
            throw new \RuntimeException('This file is damaged. Please download the template again and re-enter the data.');
        }

        return $doc;
    }

    /** 18.0 => 18, 1.2E+7 => 12000000: the way Excel shows plain numbers. */
    private static function cleanNumber(string $v): string
    {
        if ($v === '' || ! is_numeric($v)) {
            return $v;
        }
        if (stripos($v, 'e') !== false) {
            return rtrim(rtrim(number_format((float) $v, 10, '.', ''), '0'), '.');
        }

        return str_contains($v, '.') ? rtrim(rtrim($v, '0'), '.') : $v;
    }

    private static function columnIndex(string $ref): int
    {
        preg_match('/^[A-Z]+/i', $ref, $m);
        $n = 0;
        foreach (str_split(strtoupper($m[0] ?? 'A')) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n - 1;
    }

    private static function columnName(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }

        return $s;
    }

    private static function esc(string $v): string
    {
        return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
