<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentReportExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $s = $this->data['student'];
        $sum = $this->data['summary'];

        $rows = [
            ['Student Name', $s['full_name'], '', '', ''],
            ['Roll Number', $s['roll_number'], '', '', ''],
            ['Class', $s['class'], '', '', ''],
            ['Teacher', $s['teacher_name'], '', '', ''],
            ['', '', '', '', ''],
            ['Total Classes', $sum['total_classes'], 'Present', $sum['present_count'], ''],
            ['Absent', $sum['absent_count'], 'Late', $sum['late_count'], ''],
            ['Attendance %', $sum['attendance_percentage'] . '%', '', '', ''],
            ['', '', '', '', ''],
        ];

        if (empty($this->data['records'])) {
            $rows[] = ['No attendance records found.', '', '', '', ''];
        } else {
            foreach ($this->data['records'] as $r) {
                $rows[] = [$r['date'], $r['status'], $r['subject'], $r['remarks'], ''];
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Date', 'Status', 'Subject', 'Remarks', ''];
    }

    public function title(): string
    {
        return 'Student Report';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:A8')->getFont()->setBold(true);
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setWidth(20);
        }
        return [];
    }
}