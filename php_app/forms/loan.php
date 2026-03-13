<h2>Form Permohonan Pinjaman Dana</h2>
<form method="post" action="?page=forms&subpage=loan">
    <input type="text" name="name" placeholder="Nama" required class="form-control mb-2">
    <input type="text" name="member_no" placeholder="No. Anggota" class="form-control mb-2">
    <input type="text" name="ktp" placeholder="No. KTP" required class="form-control mb-2">
    <textarea name="address" placeholder="Alamat" required class="form-control mb-2"></textarea>
    <input type="text" name="phone" placeholder="No. HP" required class="form-control mb-2">
    <input type="text" name="occupation" placeholder="Pekerjaan" required class="form-control mb-2">
    <input type="number" name="amount" placeholder="Jumlah Pinjaman (Rp)" required class="form-control mb-2">
    <input type="text" name="purpose" placeholder="Keperluan" required class="form-control mb-2">
    <input type="number" name="term" placeholder="Jangka Waktu (bulan)" required class="form-control mb-2">
    <input type="text" name="collateral" placeholder="Agunan" class="form-control mb-2">
    <button type="submit" name="generate" class="btn btn-success">Generate Surat</button>
</form>
<?php
if (isset($_POST['generate'])) {
    $text = "SURAT PERMOHONAN PINJAMAN DANA\n\nPangururan, " . date('d F Y') . "\n\nKepada Yth. Pengurus KSP Lam Gabe Jaya\ndi Tempat\nHal: Permohonan Pinjaman Dana\n\nSaya yang bertanda tangan di bawah ini:\nNama : {$_POST['name']}\nNo. Anggota : {$_POST['member_no']}\nNo. KTP : {$_POST['ktp']}\nAlamat : {$_POST['address']}\nNo. HP : {$_POST['phone']}\nPekerjaan : {$_POST['occupation']}\n\nDengan ini mengajukan pinjaman sebesar Rp {$_POST['amount']} ({$_POST['amount']} rupiah) untuk keperluan {$_POST['purpose']} dengan jangka waktu {$_POST['term']} bulan.\n\nKesanggupan:\n1. Membayar angsuran pokok dan jasa sesuai jadwal yang ditetapkan koperasi.\n2. Menyediakan agunan (jika disyaratkan): {$_POST['collateral']}\nLampiran:\n1. Fotokopi KTP\n2. Kartu anggota (bagi anggota)\n3. Slip gaji/usaha (bila ada)\n4. Data agunan (bila ada)\n\nDemikian permohonan ini saya sampaikan. Terima kasih.\n\nPemohon,\n\n{$_POST['name']}";
    echo '<pre class="bg-light p-3">' . htmlspecialchars($text) . '</pre>';
    echo '<p>Salin teks di atas ke editor seperti Word untuk membuat PDF.</p>';
}
?>
