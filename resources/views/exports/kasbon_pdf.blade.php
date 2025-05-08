<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Kasbon</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Kasbon</h2>
        <p>Tanggal Cetak: {{ date('d/m/Y H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Kasbon</th>
                <th>Nama Pengaju</th>
                <th>Proyek</th>
                <th class="text-right">Jumlah (Rp)</th>
                <th>Status</th>
                <th>Tanggal Permintaan</th>
                <th>Tanggal Jatuh Tempo</th>
                <th>Tanggal Persetujuan</th>
                <th>Tanggal Pencairan</th>
                <th>Tanggal Pelunasan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($kasbons as $index => $kasbon)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $kasbon->nomor_kasbon }}</td>
                <td>{{ $kasbon->user_name }}</td>
                <td>{{ $kasbon->proyek ? $kasbon->proyek->nama_proyek : '-' }}</td>
                <td class="text-right">{{ number_format($kasbon->amount, 2, ',', '.') }}</td>
                <td>{{ $kasbon->status_label }}</td>
                <td>{{ $kasbon->kasbon_date ? date('d/m/Y', strtotime($kasbon->kasbon_date)) : '-' }}</td>
                <td>{{ $kasbon->due_date ? date('d/m/Y', strtotime($kasbon->due_date)) : '-' }}</td>
                <td>{{ $kasbon->approval_date ? date('d/m/Y', strtotime($kasbon->approval_date)) : '-' }}</td>
                <td>{{ $kasbon->disbursement_date ? date('d/m/Y', strtotime($kasbon->disbursement_date)) : '-' }}</td>
                <td>{{ $kasbon->settlement_date ? date('d/m/Y', strtotime($kasbon->settlement_date)) : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak oleh: {{ auth()->user()->name ?? 'System' }} pada {{ date('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html> 