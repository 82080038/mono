<h2>Form Surat Kesepakatan Bersama</h2>
<form method="post" action="?page=forms&subpage=agreement">
    <input type="text" name="rep_name" placeholder="Nama Perwakilan Koperasi" required class="form-control mb-2">
    <input type="text" name="borrower_name" placeholder="Nama Peminjam" required class="form-control mb-2">
    <input type="text" name="borrower_ktp" placeholder="No. KTP Peminjam" required class="form-control mb-2">
    <textarea name="borrower_address" placeholder="Alamat Peminjam" required class="form-control mb-2"></textarea>
    <input type="text" name="borrower_phone" placeholder="No. HP Peminjam" required class="form-control mb-2">
    <input type="number" name="amount" placeholder="Jumlah Pinjaman (Rp)" required class="form-control mb-2">
    <input type="number" name="term" placeholder="Jangka Waktu (bulan)" required class="form-control mb-2">
    <input type="number" step="0.01" name="interest" placeholder="Suku Bunga (%)" required class="form-control mb-2">
    <input type="text" name="collateral" placeholder="Agunan" class="form-control mb-2">
    <button type="submit" name="generate" class="btn btn-danger">Generate Surat</button>
</form>
<?php
if (isset($_POST['generate'])) {
    $text = "SURAT KESEPAKATAN BERSAMA\nNo: ……………/SKB/KSP-LGJ/" . date('Y') . "\n\nPada hari ini " . date('d') . " " . date('F') . " " . date('Y') . ", bertempat di Pangururan, kami yang bertanda tangan di bawah ini:\n\n1. Nama : {$_POST['rep_name']} (perwakilan KSP Lam Gabe Jaya)\n   Jabatan : Pengurus\n   Alamat KSP : Jl. Pulo Samosir, Pangururan, Samosir\n\n2. Nama : {$_POST['borrower_name']} (Nasabah/Peminjam)\n   No. KTP : {$_POST['borrower_ktp']}\n   Alamat : {$_POST['borrower_address']}\n   No. HP : {$_POST['borrower_phone']}\n\nMenyatakan sepakat atas pinjaman sebesar Rp {$_POST['amount']} ({$_POST['amount']} rupiah) dengan ketentuan:\n1. Jangka waktu: {$_POST['term']} bulan; jadwal angsuran: sesuai kebijakan.\n2. Jasa/bunga: {$_POST['interest']} % per bulan (sesuai kebijakan koperasi).\n3. Agunan (jika ada): {$_POST['collateral']}\n4. Keterlambatan/wanprestasi akan dikenakan sanksi sesuai peraturan koperasi.\n5. Hal-hal lain yang belum diatur akan disepakati kemudian secara tertulis.\n\nDemikian SKB ini dibuat untuk dipatuhi kedua belah pihak.\n\nPihak Koperasi,\n\n{$_POST['rep_name']}\n\nPeminjam/Nasabah,\n\n{$_POST['borrower_name']}";
    echo '<pre class="bg-light p-3">' . htmlspecialchars($text) . '</pre>';
    echo '<p>Salin teks di atas ke editor seperti Word untuk membuat PDF.</p>';
}
?>
