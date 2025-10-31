<?php
// press
$id = (int)($_GET['id'] ?? 0);
if (!$id) exit('Invalid press release');

$sql = "SELECT p.*, u.firstname, u.surname 
        FROM press_releases p
        JOIN users u ON p.created_by = u.user_id
        WHERE p.press_id = ? AND p.is_published = 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$press = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$press) exit('Press release not found');
?>