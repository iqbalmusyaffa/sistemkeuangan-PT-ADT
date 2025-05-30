<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12px; line-height: 1.5; color: #333; }
        .container { width: 100%; max-width: 800px; margin: auto; }
        .header-company { text-align: left; margin-bottom: 20px; }
        .header-company h2 { margin: 0; }
        .header-company p { margin: 0; }

        .invoice-title {
            position: relative;
            text-align: center;
            margin-top: 30px;
            margin-bottom: 40px;
        }
        .invoice-title h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .invoice-title p { margin: 0; font-size: 14px; }
        .invoice-status {
            position: absolute;
            top: -10px;
            right: -10px;
            transform: rotate(15deg);
            border: 2px solid #000;
            padding: 5px 15px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            color: #000;
            border-radius: 5px;
        }
        .invoice-status.status-paid { color: #28a745; border-color: #28a745; }
        .invoice-status.status-unpaid { color: #dc3545; border-color: #dc3545; }
        .invoice-status.status-partially_paid { color: #fd7e14; border-color: #fd7e14; }
        .invoice-status.status-cancelled { color: #6c757d; border-color: #6c757d; }

        .invoice-meta-table { width: 100%; margin-bottom: 30px; border: none; }
        .invoice-meta-table td { border: none; padding: 0; vertical-align: top; }

        .recipient { text-align: left; }
        .salutation { margin-bottom: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #999; padding: 8px; text-align: left; }
        .table th { background: #f2f2f2; }
        .table .right { text-align: right; }

        /* CSS untuk tabel ringkasan */
        .summary-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .summary-table td {
            border: 1px solid #999;
            padding: 8px;
        }
        .summary-table .grand-total td {
            background: #f2f2f2;
            font-size: 14px;
        }

        .clearfix::after { content: ""; clear: both; display: table; } /* Untuk merapikan float */

        .notes { margin-bottom: 30px; font-style: italic; font-size: 11px; clear: both; padding-top: 20px;}
        .footer { margin-top: 50px; }
        .signature { margin-top: 60px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-company">
            <h2>NAMA PERUSAHAAN ANDA</h2>
            <p>Alamat Perusahaan Anda, Kota, Kode Pos<br>
            Email: email@perusahaan.com | Telepon: (021) 123-4567</p>
        </div>
        <hr>

        <div class="invoice-title">
            <h1>Invoice</h1>
            <p>Nomor: {{ $invoice->invoice_number }}</p>

            <div class="invoice-status status-{{ $invoice->status }}">
                @if($invoice->status == 'paid')
                    LUNAS
                @elseif($invoice->status == 'unpaid')
                    BELUM DIBAYAR
                @elseif($invoice->status == 'partially_paid')
                    DIBAYAR SEBAGIAN
                @elseif($invoice->status == 'cancelled')
                    DIBATALKAN
                @else
                    {{ str_replace('_', ' ', strtoupper($invoice->status)) }}
                @endif
            </div>
        </div>

        <table class="invoice-meta-table">
            <tr>
                <td style="width: 50%;">
                    <p><strong>Tanggal Terbit:</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->isoFormat('D MMMM YYYY') }}</p>
                </td>
                <td style="width: 50%;">
                    <div class="recipient">
                        <strong>Kepada Yth.</strong><br>
                        Bapak/Ibu {{ $invoice->proyek->nama_customer ?? '-' }}<br>
                        {{ $invoice->proyek->nama_perusahaan ?? '-' }}<br>
                        {{ $invoice->proyek->alamat ?? '-' }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="salutation">
            <p>Dengan hormat,</p>
        </div>

        <p>Bersama surat ini, kami sampaikan tagihan pembayaran untuk pekerjaan pada <strong>Proyek: {{ $invoice->proyek->nama_proyek ?? '-' }}</strong>. Berikut adalah rinciannya:</p>

        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Item/Deskripsi</th>
                    <th class="right">Qty</th>
                    <th class="right">Harga Satuan</th>
                    <th class="right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->purchaseMaterials as $i => $item)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $item->item }}</td>
                    <td class="right">{{ $item->qty }}</td>
                    <td class="right">Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($item->total_harga, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="clearfix">
            <table class="summary-table">
                <tr>
                    <td>Subtotal</td>
                    <td class="right">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                </tr>

                @if($invoice->use_ppn)
                <tr>
                    <td>PPN (11%)</td>
                    <td class="right">Rp {{ number_format($invoice->ppn_amount, 0, ',', '.') }}</td>
                </tr>
                @endif

                @if($invoice->use_pph_non_final)
                <tr>
                    <td>PPH Non Final</td>
                    <td class="right">(-) Rp {{ number_format($invoice->pph_non_final_amount, 0, ',', '.') }}</td>
                </tr>
                @endif

                @if($invoice->use_pph_final)
                <tr>
                    <td>PPH Final</td>
                    <td class="right">(-) Rp {{ number_format($invoice->pph_final_amount, 0, ',', '.') }}</td>
                </tr>
                @endif

                <tr class="grand-total">
                    <td><strong>Grand Total</strong></td>
                    <td class="right"><strong>Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</strong></td>
                </tr>
            </table>
        </div>

        <p>
            Pembayaran dapat dilakukan melalui transfer ke rekening kami dengan metode pembayaran <strong>{{ $invoice->paymentMethod->nama_metode ?? '-' }}</strong>.
        </p>

        @if($invoice->notes)
            <div class="notes">
                <p><strong>Catatan:</strong><br>{{ $invoice->notes }}</p>
            </div>
        @endif

        <div class="footer">
            <p>Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p>
            <p>Hormat kami,</p>
            <div class="signature">
                <p>(_________________________)</p>
                <p><strong>[Nama Anda/Penanggung Jawab]</strong><br>
                [Jabatan Anda]</p>
            </div>
        </div>
    </div>
</body>
</html>
