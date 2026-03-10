<h1>Kelola Anggota</h1>
<h2>Tambah Anggota Baru</h2>
<form id="add_member_form">
    <input type="text" name="name" placeholder="Nama" required class="form-control mb-2">
    <input type="text" name="ktp_no" placeholder="No. KTP" required class="form-control mb-2">
    <textarea name="address" placeholder="Alamat" required class="form-control mb-2"></textarea>
    <input type="text" name="phone" placeholder="No. HP" required class="form-control mb-2">
    <input type="text" name="occupation" placeholder="Pekerjaan" required class="form-control mb-2">
    <button type="submit" class="btn btn-primary">Tambah</button>
</form>
<h2>Daftar Anggota</h2>
<table class="table">
    <thead>
        <tr>
            <th>Nama</th>
            <th>KTP</th>
            <th>Alamat</th>
            <th>HP</th>
            <th>Pekerjaan</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $members = $db->query("SELECT * FROM members");
        foreach ($members as $member) {
            echo "<tr><td>{$member['name']}</td><td>{$member['ktp_no']}</td><td>{$member['address']}</td><td>{$member['phone']}</td><td>{$member['occupation']}</td><td>{$member['status']}</td></tr>";
        }
        ?>
    </tbody>
</table>
<script>
$('#add_member_form').on('submit', function(e) {
    e.preventDefault();
    $.post('api/add_member.php', $(this).serialize(), function(data) {
        if (data.success) {
            alert('Anggota ditambahkan!');
            location.reload();
        }
    }, 'json');
});
</script>
