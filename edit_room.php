<?php
// edit_room.php
$conn = new mysqli("localhost", "root", "", "qr_support");
$conn->set_charset("utf8");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_id = $_POST['room_id'];
    $building_id = $_POST['building_id'];
    $room_number = $_POST['room_number'];
    $floor = $_POST['floor'];

    $sql = "UPDATE rooms SET building_id = '$building_id', room_number = '$room_number', floor = '$floor' WHERE room_id = '$room_id'";
    
    if ($conn->query($sql)) {
        header("Location: index.php"); // แก้ไขเสร็จกลับไปหน้าหลัก
    } else {
        echo "Error: " . $conn->error;
    }
}
?>