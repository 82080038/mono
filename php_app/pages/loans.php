<h1>Kelola Pinjaman</h1>
<h2>Tambah Pinjaman Baru</h2>
<form id="add_loan_form">
    <input type="number" name="member_id" placeholder="ID Anggota" required class="form-control mb-2">
    <input type="number" name="amount" placeholder="Jumlah (Rp)" required class="form-control mb-2">
    <input type="text" name="purpose" placeholder="Keperluan" required class="form-control mb-2">
    <input type="number" name="term_months" placeholder="Jangka Waktu (bulan)" required class="form-control mb-2">
    <input type="number" step="0.01" name="interest_rate" placeholder="Suku Bunga (%)" required class="form-control mb-2">
    <button type="submit" class="btn btn-success">Tambah</button>
</form>
<h2>Daftar Pinjaman</h2>
<table class="table">
    <thead>
        <tr>
            <th>ID Anggota</th>
            <th>Jumlah</th>
            <th>Keperluan</th>
            <th>Jangka Waktu</th>
            <th>Bunga</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $loans = $db->query("SELECT * FROM loans");
        foreach ($loans as $loan) {
            echo "<tr><td>{$loan['member_id']}</td><td>Rp " . number_format($loan['amount']) . "</td><td>{$loan['purpose']}</td><td>{$loan['term_months']} bulan</td><td>{$loan['interest_rate']}%</td><td>{$loan['status']}</td></tr>";
        }
        ?>
    </tbody>
</table>
<script>
$('#add_loan_form').on('submit', function(e) {
    e.preventDefault();
    $.post('api/add_loan.php', $(this).serialize(), function(data) {
        if (data.success) {
            alert('Pinjaman ditambahkan!');
            location.reload();
        }
    }, 'json');
});
</script>
