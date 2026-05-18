<?php
$conn = new mysqli("localhost", "root", "", "qr_support");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_id = $_POST['room_id'];
    $building_id = $_POST['building_id'];
    $problem_type = $_POST['problem_type'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    $ticket_no = "#REQ-" . date("ymd") . "-" . rand(100, 999);
    
    $image_name = NULL;
    if (isset($_FILES['repair_image']) && $_FILES['repair_image']['error'] == 0) {
        $ext = pathinfo($_FILES['repair_image']['name'], PATHINFO_EXTENSION);
        $image_name = "IMG_" . uniqid() . "." . $ext;
        $target = "uploads/" . $image_name;
        
        if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
        move_uploaded_file($_FILES['repair_image']['tmp_name'], $target);
    }

    $sql = "INSERT INTO repair_requests (ticket_no, room_id, building_id, problem_type, description, image_path, priority, status) 
            VALUES ('$ticket_no', '$room_id', '$building_id', '$problem_type', '$description', '$image_name', '$priority', 'รอดำเนินการ')";
    
    if ($conn->query($sql)) {
        echo "<script>alert('แจ้งซ่อมสำเร็จ! เลขที่ใบงาน: $ticket_no'); window.location='repair_form.php?room_id=$room_id';</script>";
    }
}
?>