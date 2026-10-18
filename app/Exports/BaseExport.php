<?php

namespace App\Exports;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderStyle;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class BaseExport
{
    protected $data;
    protected $title;
    protected $headers;
    protected $headerMerges;
    protected $centerAllHeaders;

    /**
     * @param array<int, array{0: int, 1: int}> $headerMerges Column spans on the
     *        header row (row 5), each entry being [fromColumn, toColumn] with
     *        columns indexed from 0. Lets one header label span several body
     *        columns without breaking alignment.
     */
    public function __construct($data, $title, $headers, array $headerMerges = [], bool $centerAllHeaders = false)
    {
        $this->data = $data;
        $this->title = $title;
        $this->headers = $headers;
        $this->headerMerges = $headerMerges;
        $this->centerAllHeaders = $centerAllHeaders;
    }

    public function download($filename)
    {
        $dir = storage_path('app/exports');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $timestampedName = $filename . '_' . now()->format('Y-m-d_His');
        $tempPath = $dir . '/' . $timestampedName . '.xlsx';
        
        $this->build($tempPath);

        return response()->download($tempPath, $timestampedName . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
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

        $colHeaderStyle = (new Style(
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
        ));

        $colHeaderCenteredStyle = (clone $colHeaderStyle)
            ->withCellAlignment(CellAlignment::CENTER)
            ->withCellVerticalAlignment(CellVerticalAlignment::CENTER);

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
        $headerCells = [];

        foreach ($headerValues as $colIndex => $value) {
            $isMerged = false;
            foreach ($this->headerMerges as $span) {
                // Cek apakah kolom ini berada di antara rentang merge [from, to]
                if ($colIndex >= $span[0] && $colIndex <= $span[1]) {
                    $isMerged = true;
                    break;
                }
            }

            // Jika $centerAllHeaders bernilai true ATAU kolom ini di-merge, jadikan centered
            $shouldCenter = $this->centerAllHeaders || $isMerged;
            $styleToUse = $shouldCenter ? $colHeaderCenteredStyle : $colHeaderStyle;
            
            $headerCells[] = Cell::fromValue($value, $styleToUse);
        }
        $writer->addRow(new Row($headerCells));

        // Merge header cells (row 5) so one label can span several body columns.
        foreach ($this->headerMerges as $span) {
            $writer->getOptions()->mergeCells($span[0], 5, $span[1], 5);
        }

        // Data rows
        foreach ($this->data as $record) {
            $writer->addRow(Row::fromValuesWithStyle($record, $cellStyleBorder));
        }

        $writer->close();
    }
}
