<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Termin</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Laporan Termin</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Termin</th>
                <th>Nilai Termin</th>
                <th>DP (%)</th>
                <th>Status</th>
                <th>Tanggal DP</th>
                <th>Tanggal Pelunasan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($termins as $i => $termin)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $termin->nama_termin }}</td>
                <td>{{ number_format($termin->nilai_termin, 2, ',', '.') }}</td>
                <td>{{ $termin->dp_percentage }}</td>
                <td>{{ $termin->status_termin }}</td>
                <td>{{ $termin->tanggal_dp }}</td>
                <td>{{ $termin->tanggal_pelunasan }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html> 