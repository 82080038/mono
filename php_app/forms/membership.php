<h2>Form Permohonan Menjadi Anggota</h2>
<form method="post" action="?page=forms&subpage=membership">
    <input type="text" name="name" placeholder="Nama" required class="form-control mb-2">
    <input type="text" name="ktp" placeholder="No. KTP" required class="form-control mb-2">
    <textarea name="address" placeholder="Alamat" required class="form-control mb-2"></textarea>
    <input type="text" name="phone" placeholder="No. HP" required class="form-control mb-2">
    <input type="text" name="occupation" placeholder="Pekerjaan" required class="form-control mb-2">
    <button type="submit" name="generate" class="btn btn-primary">Generate Surat</button>
</form>
<?php
if (isset($_POST['generate'])) {
    $text = "SURAT PERMOHONAN MENJADI ANGGOTA\n\nPangururan, " . date('d F Y') . "\n\nKepada Yth. Pengurus KSP Lam Gabe Jaya\ndi Tempat\nHal: Permohonan Menjadi Anggota\n\nSaya yang bertanda tangan di bawah ini:\nNama : {$_POST['name']}\nNo. KTP : {$_POST['ktp']}\nAlamat : {$_POST['address']}\nNo. HP : {$_POST['phone']}\nPekerjaan : {$_POST['occupation']}\n\nMengajukan permohonan menjadi anggota KSP Lam Gabe Jaya dan bersedia:\n1. Mematuhi AD/ART dan peraturan koperasi.\n2. Membayar simpanan pokok, wajib, dan ketentuan lain yang berlaku.\n3. Mengikuti pendidikan dasar koperasi.\nLampiran:\n1. Fotokopi KTP\n2. Pas foto 3x4 … lembar\n3. Formulir data anggota (jika ada)\n\nDemikian permohonan ini saya sampaikan. Terima kasih.\n\nPemohon,\n\n{$_POST['name']}";
    echo '<pre class="bg-light p-3">' . htmlspecialchars($text) . '</pre>';
    echo '<p>Salin teks di atas ke editor seperti Word untuk membuat PDF.</p>';
}
?>
