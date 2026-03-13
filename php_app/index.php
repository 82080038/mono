<?php
include 'db.php';
$page = $_GET['page'] ?? 'home';
$subpage = $_GET['subpage'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>KSP Lam Gabe Jaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="?page=home">KSP Lam Gabe Jaya</a>
            <div class="navbar-nav">
                <a class="nav-link" href="?page=members">Anggota</a>
                <a class="nav-link" href="?page=loans">Pinjaman</a>
                <a class="nav-link" href="?page=forms">Formulir</a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <?php
        switch ($page) {
            case 'home':
                echo '<h1>Selamat Datang di Sistem Manajemen KSP Lam Gabe Jaya</h1>';
                echo '<p>Kelola anggota, pinjaman, dan formulir dengan mudah.</p>';
                echo '<div class="row">';
                echo '<div class="col-md-4"><div class="card"><div class="card-body"><h5>Anggota</h5><p>Kelola data anggota.</p><a href="?page=members" class="btn btn-primary">Lihat</a></div></div></div>';
                echo '<div class="col-md-4"><div class="card"><div class="card-body"><h5>Pinjaman</h5><p>Kelola pinjaman.</p><a href="?page=loans" class="btn btn-success">Lihat</a></div></div></div>';
                echo '<div class="col-md-4"><div class="card"><div class="card-body"><h5>Formulir</h5><p>Buat surat.</p><a href="?page=forms" class="btn btn-warning">Buat</a></div></div></div>';
                echo '</div>';
                break;
            case 'members':
                include 'pages/members.php';
                break;
            case 'loans':
                include 'pages/loans.php';
                break;
            case 'forms':
                include 'pages/forms.php';
                break;
            default:
                echo '<h1>Halaman tidak ditemukan</h1>';
        }
        ?>
    </div>
</body>
</html>
