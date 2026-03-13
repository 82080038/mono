<?php
include '../db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $db->prepare("INSERT INTO members (name, ktp_no, address, phone, occupation, join_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['ktp_no'], $_POST['address'], $_POST['phone'], $_POST['occupation'], date('Y-m-d')]);
    echo json_encode(['success' => true]);
}
?>
