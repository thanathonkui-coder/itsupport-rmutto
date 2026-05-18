<?php
// delete_room.php
$conn = new mysqli("localhost", "root", "", "qr_support");

if (isset($_GET['id'])) {
    $room_id = $_GET['id'];
    
    // ลบข้อมูล (แบบ Cascade)
    $sql_repair = "DELETE FROM repair_requests WHERE room_id = '$room_id'";
    $conn->query($sql_repair);
    
    $sql_room = "DELETE FROM rooms WHERE room_id = '$room_id'";
    
    if ($conn->query($sql_room)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}
exit();