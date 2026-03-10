<?php
include '../db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $db->prepare("INSERT INTO loans (member_id, amount, purpose, term_months, interest_rate, start_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['member_id'], $_POST['amount'], $_POST['purpose'], $_POST['term_months'], $_POST['interest_rate'], date('Y-m-d')]);
    echo json_encode(['success' => true]);
}
?>
