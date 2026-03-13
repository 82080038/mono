<h1>Formulir</h1>
<a href="?page=forms&subpage=membership" class="btn btn-primary">Permohonan Anggota</a>
<a href="?page=forms&subpage=loan" class="btn btn-success">Permohonan Pinjaman</a>
<a href="?page=forms&subpage=job" class="btn btn-warning">Lamaran Kerja</a>
<a href="?page=forms&subpage=agreement" class="btn btn-danger">Surat Kesepakatan</a>
<?php
switch ($subpage) {
    case 'membership':
        include 'forms/membership.php';
        break;
    case 'loan':
        include 'forms/loan.php';
        break;
    case 'job':
        include 'forms/job.php';
        break;
    case 'agreement':
        include 'forms/agreement.php';
        break;
}
?>
