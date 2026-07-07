<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceReportExport implements WithMultipleSheets
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            'Summary'  => new ReportSummarySheet($this->data),
            'Students' => new ReportStudentsSheet($this->data['students']),
        ];
    }
}