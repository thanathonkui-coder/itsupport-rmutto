<?php
// save_room.php
$conn = new mysqli("localhost", "root", "", "qr_support");
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $b_id = $_POST['building_id'];
    $r_num = $_POST['room_number'];
    $floor = $_POST['floor'];

    $sql = "INSERT INTO rooms (building_id, room_number, floor) VALUES ('$b_id', '$r_num', '$floor')";
    if ($conn->query($sql)) {
        header("Location: index.php"); // กลับไปหน้าหลัก
    } else {
        echo "Error: " . $conn->error;
    }
}
?>