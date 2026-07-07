<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportSummarySheet implements FromArray, WithTitle, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $f = $this->data['filters'];
        $s = $this->data['stats'];

        return [
            ['DAVS - Attendance Report'],
            ['Generated On', $this->data['generated_at']],
            [],
            ['Applied Filters'],
            ['Class', $f['class']],
            ['Teacher', $f['teacher']],
            ['Period', $f['period']],
            ['Status', $f['status']],
            ['Student Name', $f['student_name']],
            ['Session ID', $f['session_id']],
            [],
            ['Attendance Statistics'],
            ['Total Sessions', $s['total_sessions']],
            ['Total Students', $s['total_students']],
            ['Attendance %', $s['attendance_pct'] . '%'],
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A4')->getFont()->setBold(true);
        $sheet->getStyle('A12')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(30);
        return [];
    }
}