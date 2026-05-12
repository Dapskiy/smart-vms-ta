<?php

namespace App\Filament\Exports;

use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class SummaryExcelExport extends ExcelExport
{
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Ambil jumlah baris data sebelum digeser
                $highestRow = $sheet->getHighestRow();
                
                // 1. Geser tabel data ke bawah 4 baris untuk Kop Laporan
                $sheet->insertNewRowBefore(1, 4);
                
                // 2. Format Kop Laporan (Merge A - G, Bold, Center)
                // Baris 1: Judul
                $sheet->mergeCells('A1:G1'); 
                $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI KUNJUNGAN TAMU');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Baris 2: Nama Perusahaan
                $sheet->mergeCells('A2:G2');
                $sheet->setCellValue('A2', 'PT VISITA');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Baris 3: Periode Kunjungan
                $sheet->mergeCells('A3:G3');
                Carbon::setLocale('id'); // Menggunakan bahasa Indonesia
                $startDate = request('start_date') ? Carbon::parse(request('start_date'))->translatedFormat('d F Y') : 'Awal';
                $endDate = request('end_date') ? Carbon::parse(request('end_date'))->translatedFormat('d F Y') : 'Sekarang';
                $sheet->setCellValue('A3', 'Periode: ' . $startDate . ' s.d. ' . $endDate);
                $sheet->getStyle('A3')->getFont()->setBold(true)->setItalic(true);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 3. Format Header Tabel (Baris 5) -> Bold & Fill Color (Abu-abu terang)
                $headerRange = 'A5:G5';
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9D9D9'); // Warna abu-abu hex #D9D9D9

                // 4. Buat Border untuk seluruh tabel (Baris 5 sampai baris data terakhir)
                // highestRow + 4 karena baris sudah kita geser ke bawah sebanyak 4 baris
                $tableRange = 'A5:G' . ($highestRow + 4); 
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }
        ];
    }
}
