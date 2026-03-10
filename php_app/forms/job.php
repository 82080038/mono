<h2>Form Lamaran Kerja</h2>
<form method="post" action="?page=forms&subpage=job">
    <input type="text" name="name" placeholder="Nama" required class="form-control mb-2">
    <input type="text" name="birth" placeholder="Tempat/Tgl Lahir" required class="form-control mb-2">
    <textarea name="address" placeholder="Alamat" required class="form-control mb-2"></textarea>
    <input type="text" name="phone" placeholder="No. HP" required class="form-control mb-2">
    <input type="text" name="education" placeholder="Pendidikan Terakhir" required class="form-control mb-2">
    <input type="text" name="position" placeholder="Posisi yang Dilamar" required class="form-control mb-2">
    <button type="submit" name="generate" class="btn btn-warning">Generate Surat</button>
</form>
<?php
if (isset($_POST['generate'])) {
    $text = "SURAT LAMARAN KERJA\n\nPangururan, " . date('d F Y') . "\n\nKepada Yth. Pengurus KSP Lam Gabe Jaya\ndi Tempat\nHal: Lamaran Kerja\n\nSaya yang bertanda tangan di bawah ini:\nNama : {$_POST['name']}\nTempat/Tgl lahir : {$_POST['birth']}\nAlamat : {$_POST['address']}\nNo. HP : {$_POST['phone']}\nPendidikan terakhir : {$_POST['education']}\nPosisi yang dilamar : {$_POST['position']}\n\nDengan ini mengajukan lamaran kerja pada KSP Lam Gabe Jaya. Sebagai bahan pertimbangan, saya lampirkan:\n1. Fotokopi KTP\n2. CV/Daftar Riwayat Hidup\n3. Ijazah & Transkrip\n4. SKCK (bila ada)\n5. Sertifikat pendukung (bila ada)\n\nDemikian permohonan ini saya ajukan. Atas perhatian Bapak/Ibu, saya ucapkan terima kasih.\n\nHormat saya,\n\n{$_POST['name']}";
    echo '<pre class="bg-light p-3">' . htmlspecialchars($text) . '</pre>';
    echo '<p>Salin teks di atas ke editor seperti Word untuk membuat PDF.</p>';
}
?>
