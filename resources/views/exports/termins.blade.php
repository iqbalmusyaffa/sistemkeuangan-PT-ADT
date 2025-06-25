<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Termin Proyek</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 20px;
        }
        body { font-family: sans-serif; font-size: 11px; }
        h3, h4 { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .summary-table td { border: none; padding: 3px; }
    </style>
</head>
<body>

    <h3>Laporan Termin Proyek</h3>

    <table class="summary-table">
    <tr>
        <td><strong>Nama Proyek</strong></td>
        <td>: {{ $project->nama_proyek ?? '-' }}</td>
        <td><strong>Customer</strong></td>
        <td>: {{ $project->nama_customer ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Nilai Kontrak</strong></td>
        <td>: Rp {{ number_format($project->anggaran_kontrak ?? 0, 0, ',', '.') }}</td>
        <td><strong>Budget Disesuaikan</strong></td>
        <td>: Rp {{ number_format($project->budget_adjusted ?? $project->anggaran_kontrak ?? 0, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td><strong>Total Nilai Termin</strong></td>
        <td>: Rp {{ number_format($termins->sum('nilai_termin'), 0, ',', '.') }}</td>
        <td><strong>Status Proyek</strong></td>
        <td>: {{ $project->status_proyek ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Total Amount Invoice</strong></td>
        <td>: Rp {{ number_format($invoice->grand_total ?? 0, 0, ',', '.') }}</td>
        <td><strong>Total DP Dibayar</strong></td>
        <td>: Rp {{ number_format($total_income_dp ?? 0, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td><strong>Total Pelunasan Dibayar</strong></td>
        <td>: Rp {{ number_format($total_income_pelunasan ?? 0, 0, ',', '.') }}</td>
        <td><strong>Total Dibayar</strong></td>
        <td>: Rp {{ number_format($total_income_all ?? 0, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td><strong>Sisa Belum Dibayar</strong></td>
        <td colspan="3">Rp {{ number_format($sisa_belum_dibayar ?? 0, 0, ',', '.') }}</td>
    </tr>
</table>


    <br>
    <h4>Detail Termin</h4>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Termin</th>
                <th>Jenis</th>
                <th>Termin Ke</th>
                <th>Target Progress</th>
                <th>Progress Pekerjaan</th>
                <th>Nilai Termin</th>
                <th>Persentase DP</th>
                <th>Nilai DP</th>
                <th>Nilai Pelunasan</th>
                <th>Tgl DP</th>
                <th>Tgl Pelunasan</th>
                <th>Status Termin</th>
                <th>Status Approval</th>
                <th>Approved By</th>
                <th>Approved At</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($termins as $index => $termin)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $termin->nama_termin }}</td>
                    <td>{{ $termin->jenis_termin }}</td>
                    <td class="text-center">{{ $termin->termin_ke }}</td>
                    <td class="text-center">{{ $termin->target_progress }}%</td>
                    <td class="text-center">{{ $termin->project_progress ?? '-' }}%</td>
                    <td class="text-right">Rp {{ number_format($termin->nilai_termin, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $termin->persentase_dp }}%</td>
                    <td class="text-right">Rp {{ number_format($termin->nilai_dp, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($termin->nilai_pelunasan, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $termin->tanggal_dp ? \Carbon\Carbon::parse($termin->tanggal_dp)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $termin->tanggal_pelunasan ? \Carbon\Carbon::parse($termin->tanggal_pelunasan)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $termin->status_termin }}</td>
                    <td class="text-center">{{ $termin->status_approval }}</td>
                    <td class="text-center">{{ $termin->approved_by_name ?? '-' }}</td>
                    <td class="text-center">
                        {{ $termin->approved_at ? \Carbon\Carbon::parse($termin->approved_at)->format('d/m/Y') : '-' }}
                    </td>
                    <td>{{ $termin->keterangan }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 40px;">Dicetak pada: {{ now()->format('d-m-Y H:i') }}</p>
</body>
</html>
