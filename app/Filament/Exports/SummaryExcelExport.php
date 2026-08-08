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
                
                // 2. Format Kop Laporan (Merge A - H, Bold, Center) karena ketambahan kolom No
                // Baris 1: Judul
                $sheet->mergeCells('A1:I1'); 
                $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI KUNJUNGAN TAMU');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Baris 2: Nama Perusahaan
                $sheet->mergeCells('A2:I2');
                $sheet->setCellValue('A2', 'PT VISITA');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Baris 3: Periode Kunjungan
                $sheet->mergeCells('A3:I3');
                \Carbon\Carbon::setLocale('id');

                $referer = request()->header('referer');
                parse_str(parse_url($referer, PHP_URL_QUERY) ?? '', $urlParams);
                $type = $urlParams['type'] ?? 'range';
                $periodeText = 'Keseluruhan (Awal s.d. Sekarang)';

                if ($type === 'month' && !empty($urlParams['month']) && !empty($urlParams['year'])) {
                    $dateObj = \Carbon\Carbon::createFromDate($urlParams['year'], $urlParams['month'], 1);
                    $periodeText = $dateObj->translatedFormat('F Y');
                } elseif ($type === 'year' && !empty($urlParams['year'])) {
                    $periodeText = 'Tahun ' . $urlParams['year'];
                } else { // type === 'range'
                    $start = !empty($urlParams['start_date']) ? \Carbon\Carbon::parse($urlParams['start_date'])->translatedFormat('d F Y') : null;
                    $end = !empty($urlParams['end_date']) ? \Carbon\Carbon::parse($urlParams['end_date'])->translatedFormat('d F Y') : null;

                    if ($start && $end) {
                        $periodeText = $start . ' - ' . $end;
                    } elseif ($start) {
                        $periodeText = 'Sejak ' . $start;
                    } elseif ($end) {
                        $periodeText = 'Sampai ' . $end;
                    }
                }

                $sheet->setCellValue('A3', 'Periode: ' . $periodeText);
                $sheet->getStyle('A3')->getFont()->setBold(true)->setItalic(true);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 3. Format Header Tabel (Baris 5) -> Bold & Fill Color (Abu-abu terang)
                $headerRange = 'A5:I5';
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9D9D9'); // Warna abu-abu hex #D9D9D9

                // 4. Buat Border untuk seluruh tabel (Baris 5 sampai baris data terakhir)
                // highestRow + 4 karena baris sudah kita geser ke bawah sebanyak 4 baris
                $tableRange = 'A5:I' . ($highestRow + 4); 
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }
        ];
    }
}
