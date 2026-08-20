<?php

namespace App\Exports;

use Illuminate\Http\StreamedResponse;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;

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

    public function download($filename): StreamedResponse
    {
        $dir = public_path('exports');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $tempPath = $dir . '/' . $filename . '_' . uniqid() . '.xlsx';
        $this->build($tempPath);

        $fileToServe = $tempPath;

        return response()->stream(function () use ($fileToServe) {
            readfile($fileToServe);
            flush();
            @unlink($fileToServe);
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xlsx"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    protected function build(string $path): void
    {
        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($path);

        $headerStyle = (new Style())
            ->setFontSize(14)
            ->setBold(true)
            ->setFontName('Arial');

        $labelStyle = (new Style())
            ->setFontSize(10)
            ->setFontName('Arial');

        $colHeaderStyle = (new Style())
            ->setFontSize(10)
            ->setBold(true)
            ->setFontName('Arial')
            ->setBackgroundColor(new Color('043523'))
            ->setFontColor(new Color('FFFFFF'))
            ->setBorder(new Border(
                Border::BOTTOM,
                Border::STYLE_THIN,
                new Color('0F513A')
            ));

        $cellStyleBorder = (new Style())
            ->setFontSize(10)
            ->setFontName('Arial')
            ->setBorder(new Border(
                Border::BOTTOM,
                Border::STYLE_THIN,
                new Color('DEE2E6')
            ));

        // Row 1: Title
        $row = WriterEntityFactory::createRow();
        $cell = WriterEntityFactory::createCell($this->title);
        $cell->setStyle($headerStyle);
        $row->setCells([$cell]);
        $writer->addRow($row);

        // Row 2: Empty
        $writer->addRow(WriterEntityFactory::createRow());

        // Row 3: Downloaded on
        $timestamp = now()->format('d/m/Y, H:i') . ' WIB';
        $row = WriterEntityFactory::createRow();
        $cell = WriterEntityFactory::createCell(__('general.downloaded_on') . ': ' . $timestamp);
        $cell->setStyle($labelStyle);
        $row->setCells([$cell]);
        $writer->addRow($row);

        // Row 4: Downloaded by
        $row = WriterEntityFactory::createRow();
        $cell = WriterEntityFactory::createCell(__('general.downloaded_by') . ': -');
        $cell->setStyle($labelStyle);
        $row->setCells([$cell]);
        $writer->addRow($row);

        // Row 5: Empty
        $writer->addRow(WriterEntityFactory::createRow());

        // Row 6: Column headers
        $row = WriterEntityFactory::createRow();
        $cells = [];
        foreach ($this->headers as $header) {
            $cell = WriterEntityFactory::createCell($header);
            $cell->setStyle($colHeaderStyle);
            $cells[] = $cell;
        }
        $row->setCells($cells);
        $writer->addRow($row);

        // Data rows
        foreach ($this->data as $index => $record) {
            $row = WriterEntityFactory::createRow();
            $cells = [];
            foreach ($record as $value) {
                $cell = WriterEntityFactory::createCell($value ?? '-');
                $cell->setStyle($cellStyleBorder);
                $cells[] = $cell;
            }
            $row->setCells($cells);
            $writer->addRow($row);
        }

        $writer->close();
    }
}
