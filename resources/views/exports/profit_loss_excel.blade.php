<table>
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Deskripsi</th>
            <th>Jenis</th>
            <th>Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($incomes as $income)
            <tr>
                <td>{{ $income->tanggal }}</td>
                <td>{{ $income->deskripsi ?? '-' }}</td>
                <td>Income</td>
                <td>{{ $income->jumlah }}</td>
            </tr>
        @endforeach

        @foreach ($expenses as $expense)
            <tr>
                <td>{{ $expense->transaction_date }}</td>
                <td>{{ $expense->description ?? '-' }}</td>
                <td>Expense</td>
                <td>{{ $expense->amount }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
