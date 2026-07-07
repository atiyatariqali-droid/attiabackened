<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportStudentsSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        if (empty($this->rows)) {
            return [['No attendance records found for the selected filters.', '', '', '', '', '', '', '', '']];
        }

        return array_map(fn($r) => [
            $r['student_name'], $r['roll_no'], $r['class_name'], $r['teacher_name'],
            $r['present'], $r['absent'], $r['total'], $r['pct'] . '%', $r['status'],
        ], $this->rows);
    }

    public function headings(): array
    {
        return ['Student Name', 'Roll No', 'Class', 'Teacher', 'Present', 'Absent', 'Total', 'Attendance %', 'Status'];
    }

    public function title(): string
    {
        return 'Students';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setWidth(18);
        }
        return [];
    }
}