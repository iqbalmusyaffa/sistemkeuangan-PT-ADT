<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Termin - {{ $project->nama_proyek }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
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
            background-color: #f5f5f5;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Data Termin - {{ $project->nama_proyek }}</h2>
        <p>Tanggal Export: {{ date('d/m/Y H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Termin</th>
                <th>Jenis Termin</th>
                <th>Termin Ke</th>
                <th>Nilai Termin</th>
                <th>Persentase DP</th>
                <th>Nilai DP</th>
                <th>Nilai Pelunasan</th>
                <th>Status</th>
                <th>Status Approval</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($termins as $index => $termin)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $termin->nama_termin }}</td>
                <td>{{ $termin->jenis_termin }}</td>
                <td class="text-center">{{ $termin->termin_ke }}</td>
                <td class="text-right">Rp {{ number_format($termin->nilai_termin, 0, ',', '.') }}</td>
                <td class="text-center">{{ $termin->persentase_dp }}%</td>
                <td class="text-right">Rp {{ number_format($termin->nilai_dp, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($termin->nilai_pelunasan, 0, ',', '.') }}</td>
                <td>{{ $termin->status_termin }}</td>
                <td>{{ $termin->status_approval }}</td>
                <td>{{ $termin->keterangan }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><strong>Total:</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($termins->sum('nilai_termin'), 0, ',', '.') }}</strong></td>
                <td></td>
                <td class="text-right"><strong>Rp {{ number_format($termins->sum('nilai_dp'), 0, ',', '.') }}</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($termins->sum('nilai_pelunasan'), 0, ',', '.') }}</strong></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Dicetak oleh: {{ auth()->user()->name }}</p>
    </div>
</body>
</html> 