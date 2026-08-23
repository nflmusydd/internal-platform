<?php

namespace App\Exports;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderStyle;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class BaseExport
{
    protected $data;
    protected $title;
    protected $headers;

    public function __construct($data, $title, $headers)
    {
        $this->data = $data;
        $this->title = $title;
        $this->headers = $headers;
    }

    public function download($filename)
    {
        $dir = public_path('exports');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $timestampedName = $filename . '_' . now()->format('Y-m-d_His');
        $tempPath = $dir . '/' . $timestampedName . '.xlsx';
        $this->build($tempPath);

        $fileToServe = $tempPath;

        return response()->stream(function () use ($fileToServe) {
            readfile($fileToServe);
            flush();
            @unlink($fileToServe);
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $timestampedName . '.xlsx"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    protected function build(string $path): void
    {
        $writer = new Writer();
        $writer->openToFile($path);

        // Auto column width based on data content
        $allRows = array_merge([$this->headers], $this->data);
        $colWidths = [];
        foreach ($allRows as $row) {
            foreach ($row as $colIndex => $value) {
                $len = mb_strlen((string) $value);
                $colWidths[$colIndex] = max($colWidths[$colIndex] ?? 0, $len);
            }
        }
        foreach ($colWidths as $colIndex => $maxLen) {
            $width = min(50, max(10, $maxLen * 1.5 + 4));
            $writer->getOptions()->setColumnWidth($width, $colIndex + 1);
        }

        $headerStyle = new Style(
            fontSize: 10,
            fontName: 'Arial',
        );

        $labelStyle = new Style(
            fontSize: 10,
            fontName: 'Arial',
        );

        $colHeaderStyle = new Style(
            fontBold: true,
            fontSize: 10,
            fontName: 'Arial',
            fontColor: Color::WHITE,
            backgroundColor: Color::toARGB('043523'),
            border: new Border(
                new BorderPart(name: BorderName::LEFT, color: '000000', width: BorderWidth::THIN, style: BorderStyle::SOLID),
                new BorderPart(name: BorderName::RIGHT, color: '000000', width: BorderWidth::THIN, style: BorderStyle::SOLID),
                new BorderPart(name: BorderName::TOP, color: '000000', width: BorderWidth::THIN, style: BorderStyle::SOLID),
                new BorderPart(name: BorderName::BOTTOM, color: '000000', width: BorderWidth::THIN, style: BorderStyle::SOLID),
            ),
        );

        $cellStyleBorder = new Style(
            fontSize: 10,
            fontName: 'Arial',
            border: new Border(
                new BorderPart(name: BorderName::LEFT, color: '000000', width: BorderWidth::THIN, style: BorderStyle::SOLID),
                new BorderPart(name: BorderName::RIGHT, color: '000000', width: BorderWidth::THIN, style: BorderStyle::SOLID),
                new BorderPart(name: BorderName::TOP, color: '000000', width: BorderWidth::THIN, style: BorderStyle::SOLID),
                new BorderPart(name: BorderName::BOTTOM, color: '000000', width: BorderWidth::THIN, style: BorderStyle::SOLID),
            ),
        );

        $emptyStyle = new Style();

        // Row 1: Title
        $writer->addRow(Row::fromValuesWithStyle([__('general.title') . ': ' . $this->title], $headerStyle));

        // Row 2: Downloaded on
        $timestamp = now()->format('d/m/Y, H:i') . ' WIB';
        $writer->addRow(Row::fromValuesWithStyle([__('general.downloaded_on') . ': ' . $timestamp], $labelStyle));

        // Row 3: Downloaded by
        $downloadedBy = auth()->check() ? auth()->user()->name : '-';
        $writer->addRow(Row::fromValuesWithStyle([__('general.downloaded_by') . ': ' . $downloadedBy], $labelStyle));

        // Row 4: Empty
        $writer->addRow(Row::fromValuesWithStyle([''], $emptyStyle));

        // Row 5: Column headers
        $headerValues = array_values($this->headers);
        $writer->addRow(Row::fromValuesWithStyle($headerValues, $colHeaderStyle));

        // Data rows
        foreach ($this->data as $record) {
            $writer->addRow(Row::fromValuesWithStyle($record, $cellStyleBorder));
        }

        $writer->close();
    }
}
