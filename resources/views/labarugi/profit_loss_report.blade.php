<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        .summary { margin-top: 20px; }
        .summary td { border: none; padding: 4px 6px; }
        h2 { text-align: center; margin-bottom: 0; }
        .info { margin-top: 10px; }
    </style>
</head>
<body>
    <h2>Laporan Laba Rugi</h2>
    <div class="info">
        <strong>Periode:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Deskripsi</th>
                <th>Jenis</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $t)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($t->transaction_date ?? $t->tanggal)->format('d-m-Y') }}</td>
                    <td>{{ $t->description ?? $t->deskripsi }}</td>
                    <td>{{ ucfirst($t->type ?? 'n/a') }}</td>
                    <td>Rp {{ number_format($t->amount ?? $t->jumlah, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center">Tidak ada data transaksi</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td><strong>Total Pendapatan</strong></td>
            <td>: Rp {{ number_format($totalIncome, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Total Pengeluaran</strong></td>
            <td>: Rp {{ number_format($totalExpense, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Laba/Rugi Bersih</strong></td>
            <td>
                : <strong style="color: {{ $netProfit >= 0 ? 'green' : 'red' }}">
                    Rp {{ number_format($netProfit, 2, ',', '.') }}
                </strong>
            </td>
        </tr>
    </table>
</body>
</html>
