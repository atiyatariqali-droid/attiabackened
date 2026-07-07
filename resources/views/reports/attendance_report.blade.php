<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
    h1 { font-size: 18px; margin-bottom: 2px; }
    .muted { color: #777; margin-bottom: 16px; }
    .filters-box, .stats-box { border: 1px solid #ddd; padding: 10px 14px; margin-bottom: 16px; border-radius: 4px; }
    .filters-box h3, .stats-box h3 { margin: 0 0 8px 0; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border: 1px solid #ddd; padding: 6px 8px; font-size: 10px; text-align: left; }
    th { background: #f5f5f5; }
    .stat-row td { padding: 4px 8px; }
    .empty { text-align: center; padding: 20px; color: #999; }
</style>
</head>
<body>
    <h1>DAVS - Attendance Report</h1>
    <div class="muted">Generated on {{ $generated_at }}</div>

    <div class="filters-box">
        <h3>Applied Filters</h3>
        <table>
            <tr><td><strong>Class</strong></td><td>{{ $filters['class'] }}</td>
                <td><strong>Teacher</strong></td><td>{{ $filters['teacher'] }}</td></tr>
            <tr><td><strong>Period</strong></td><td>{{ $filters['period'] }}</td>
                <td><strong>Status</strong></td><td>{{ $filters['status'] }}</td></tr>
            <tr><td><strong>Student Name</strong></td><td>{{ $filters['student_name'] }}</td>
                <td><strong>Session ID</strong></td><td>{{ $filters['session_id'] }}</td></tr>
        </table>
    </div>

    <div class="stats-box">
        <h3>Attendance Statistics</h3>
        <table>
            <tr>
                <td><strong>Total Sessions:</strong> {{ $stats['total_sessions'] }}</td>
                <td><strong>Total Students:</strong> {{ $stats['total_students'] }}</td>
                <td><strong>Attendance %:</strong> {{ $stats['attendance_pct'] }}%</td>
            </tr>
        </table>
    </div>

    <h3>Student Records</h3>
    @if(count($students) === 0)
        <div class="empty">No attendance records found for the selected filters.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Student</th><th>Roll No</th><th>Class</th><th>Teacher</th>
                    <th>Present</th><th>Absent</th><th>Total</th><th>Attendance %</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $s)
                <tr>
                    <td>{{ $s['student_name'] }}</td>
                    <td>{{ $s['roll_no'] }}</td>
                    <td>{{ $s['class_name'] }}</td>
                    <td>{{ $s['teacher_name'] }}</td>
                    <td>{{ $s['present'] }}</td>
                    <td>{{ $s['absent'] }}</td>
                    <td>{{ $s['total'] }}</td>
                    <td>{{ $s['pct'] }}%</td>
                    <td>{{ $s['status'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>