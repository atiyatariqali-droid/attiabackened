<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
    h1 { font-size: 18px; margin-bottom: 2px; }
    .muted { color: #777; margin-bottom: 16px; }
    .box { border: 1px solid #ddd; padding: 10px 14px; margin-bottom: 16px; border-radius: 4px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border: 1px solid #ddd; padding: 6px 8px; font-size: 10px; text-align: left; }
    th { background: #f5f5f5; }
    .empty { text-align: center; padding: 20px; color: #999; }
</style>
</head>
<body>
    <h1>Student Attendance Report</h1>
    <div class="muted">Generated on {{ $generated_at }}</div>

    <div class="box">
        <table>
            <tr><td><strong>Full Name</strong></td><td>{{ $student['full_name'] }}</td>
                <td><strong>Roll Number</strong></td><td>{{ $student['roll_number'] }}</td></tr>
            <tr><td><strong>Class</strong></td><td>{{ $student['class'] }}</td>
                <td><strong>Teacher</strong></td><td>{{ $student['teacher_name'] }}</td></tr>
        </table>
    </div>

    <div class="box">
        <table>
            <tr>
                <td><strong>Total Classes:</strong> {{ $summary['total_classes'] }}</td>
                <td><strong>Present:</strong> {{ $summary['present_count'] }}</td>
                <td><strong>Absent:</strong> {{ $summary['absent_count'] }}</td>
                <td><strong>Late:</strong> {{ $summary['late_count'] }}</td>
                <td><strong>Attendance %:</strong> {{ $summary['attendance_percentage'] }}%</td>
            </tr>
        </table>
    </div>

    <h3>Attendance History</h3>
    @if(count($records) === 0)
        <div class="empty">No attendance records found.</div>
    @else
        <table>
            <thead><tr><th>Date</th><th>Status</th><th>Subject</th><th>Remarks</th></tr></thead>
            <tbody>
                @foreach($records as $r)
                <tr>
                    <td>{{ $r['date'] }}</td>
                    <td>{{ $r['status'] }}</td>
                    <td>{{ $r['subject'] }}</td>
                    <td>{{ $r['remarks'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>