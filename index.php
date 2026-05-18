<?php
session_start();

/** * 1. Database Connection 
 */
$host = "localhost";
$user = "root";
$pass = "";
$db   = "qr_support";
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8");

if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

// --- ฟังก์ชันสำหรับ AI วิเคราะห์ปัญหาและอุปกรณ์ ---
function getAIRecommendation($type) {
    $advice = [
        'เน็ตเวิร์ก' => [
            'tools' => 'สาย LAN, ตัวทดสอบสัญญาณ (Tester), คีมเข้าหัว RJ45',
            'fix' => 'ตรวจสอบการ Loop ของสายภายใน หรือรีเซ็ต Switch Hub'
        ],
        'คอมพิวเตอร์' => [
            'tools' => 'ไขควงชุด, ยางลบ (ขัดแรม), แผ่นบูตสำรอง',
            'fix' => 'เช็คจุดเชื่อมต่อ RAM/GPU หรือตรวจสอบไฟล์ระบบ (SFC scan)'
        ],
        'เครื่องพิมพ์' => [
            'tools' => 'น้ำยาล้างหัวพิมพ์, ตลับหมึกสำรอง, กระดาษทดสอบ',
            'fix' => 'ทำความสะอาดลูกยางดึงกระดาษ หรือสั่งล้างหัวพิมพ์ผ่านซอฟต์แวร์'
        ],
        'ซอฟต์แวร์' => [
            'tools' => 'Flash Drive ติดตั้ง OS, External Hard Drive',
            'fix' => 'ถอนการติดตั้งโปรแกรมล่าสุดที่มีปัญหา หรือทำ System Restore'
        ]
    ];
    return $advice[$type] ?? ['tools' => 'ชุดเครื่องมือช่างพื้นฐาน', 'fix' => 'ตรวจสอบความผิดปกติหน้างานเบื้องต้น'];
}

/** * 2. Data Retrieval for KPI Cards 
 */
$total_all = $conn->query("SELECT COUNT(*) as cnt FROM repair_requests")->fetch_assoc()['cnt'];
$total_done = $conn->query("SELECT COUNT(*) as cnt FROM repair_requests WHERE status = 'เสร็จสิ้น'")->fetch_assoc()['cnt'];
$total_pending = $conn->query("SELECT COUNT(*) as cnt FROM repair_requests WHERE status = 'รอดำเนินการ'")->fetch_assoc()['cnt'];
$total_sla = $conn->query("SELECT COUNT(*) as cnt FROM repair_requests WHERE status != 'เสร็จสิ้น' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['cnt'];

/** * 3. Weekly Trend (Line Chart) 
 */
$line_values = [];
for ($i = 3; $i >= 0; $i--) {
    $line_query = $conn->query("SELECT COUNT(*) as cnt FROM repair_requests WHERE WEEK(created_at) = WEEK(NOW()) - $i");
    $line_values[] = (int)$line_query->fetch_assoc()['cnt'];
}

/** * 4. Monthly Stats (Bar Chart) 
 */
$bar_labels = []; $bar_values = [];
for ($i = 5; $i >= 0; $i--) {
    $month_query = $conn->query("SELECT MONTHNAME(DATE_SUB(NOW(), INTERVAL $i MONTH)) as m_name, COUNT(*) as cnt 
                                FROM repair_requests 
                                WHERE MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL $i MONTH))");
    $res = $month_query->fetch_assoc();
    $bar_labels[] = $res['m_name'] ?? 'N/A';
    $bar_values[] = (int)($res['cnt'] ?? 0);
}

/** * 5. Map Data (ดึงพิกัดจากตาราง buildings) 
 */
$map_data = [];
$buildings_query = $conn->query("
    SELECT b.building_name, b.latitude, b.longitude, 
    COUNT(CASE WHEN r.status != 'เสร็จสิ้น' THEN 1 END) as job_count 
    FROM buildings b 
    LEFT JOIN repair_requests r ON b.building_id = r.building_id 
    GROUP BY b.building_id
");
while($row = $buildings_query->fetch_assoc()) {
    $map_data[] = [
        "name"   => $row['building_name'],
        "count"  => (int)$row['job_count'],
        "coords" => [(float)$row['longitude'], (float)$row['latitude']], 
        "color"  => ($row['job_count'] > 0 ? "#ef4444" : "#3b82f6")
    ];
}

/** * 6. Pie Chart Data 
 */
$pie_labels = []; $pie_values = [];
$pie_query = $conn->query("SELECT problem_type, COUNT(*) as cnt FROM repair_requests GROUP BY problem_type");
while($row = $pie_query->fetch_assoc()) {
    $pie_labels[] = $row['problem_type'];
    $pie_values[] = (int)$row['cnt'];
}

/** * 7. Repair List Table 
 */
$repair_list = $conn->query("SELECT r.*, b.building_name, rm.room_number FROM repair_requests r LEFT JOIN buildings b ON r.building_id = b.building_id LEFT JOIN rooms rm ON r.room_id = rm.room_id ORDER BY r.created_at DESC LIMIT 15");

/** * 8. Kanban Data 
 */
$tasks_new = $conn->query("SELECT r.*, b.building_name FROM repair_requests r LEFT JOIN buildings b ON r.building_id = b.building_id WHERE r.status = 'รอดำเนินการ' ORDER BY r.priority DESC");
$tasks_doing = $conn->query("SELECT r.*, b.building_name, u.fullname as tech_name FROM repair_requests r LEFT JOIN buildings b ON r.building_id = b.building_id LEFT JOIN users u ON r.technician_id = u.user_id WHERE r.status = 'กำลังดำเนินการ'");
$tasks_check = $conn->query("SELECT r.*, b.building_name FROM repair_requests r LEFT JOIN buildings b ON r.building_id = b.building_id WHERE r.status = 'รอตรวจสอบ'");
$tasks_done_kanban = $conn->query("SELECT r.*, b.building_name FROM repair_requests r LEFT JOIN buildings b ON r.building_id = b.building_id WHERE r.status = 'เสร็จสิ้น' LIMIT 5");

/** * 9. Room & QR Data 
 */
$rooms_list = $conn->query("SELECT r.*, b.building_name, r.building_id FROM rooms r JOIN buildings b ON r.building_id = b.building_id ORDER BY b.building_name ASC, r.room_number ASC");
$buildings_dropdown = $conn->query("SELECT building_id, building_name FROM buildings");

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>IT Support Dashboard - RMUTTO</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet"/>
    
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-dark: #0f172a; --bg-panel: #1e293b; --bg-sidebar: #0f172a;
            --text-main: #f8fafc; --text-muted: #94a3b8; --border-color: #334155;
            --primary: #3b82f6; --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --purple: #8b5cf6;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Sarabun', sans-serif; background-color: var(--bg-dark); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }

        /* Sidebar (Desktop) */
        .sidebar { width: 260px; background-color: var(--bg-sidebar); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding-top: 20px; transition: 0.3s; z-index: 100; }
        .sidebar-brand { display: flex; align-items: center; gap: 12px; padding: 0 20px 20px 20px; border-bottom: 1px solid var(--border-color); }
        .sidebar-brand img { width: 40px; }
        .nav-menu { list-style: none; padding: 20px 0; flex: 1; }
        .nav-menu li a { display: flex; align-items: center; gap: 15px; padding: 12px 25px; color: var(--text-muted); text-decoration: none; font-size: 14px; transition: 0.3s; }
        .nav-menu li.active a, .nav-menu li a:hover { color: var(--text-main); background-color: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }

        /* Bottom Nav (Mobile) */
        .mobile-nav { display: none; position: fixed; bottom: 0; left: 0; right: 0; background: var(--bg-panel); border-top: 1px solid var(--border-color); height: 70px; z-index: 1000; justify-content: space-around; align-items: center; padding-bottom: env(safe-area-inset-bottom); }
        .mobile-nav-item { color: var(--text-muted); text-decoration: none; display: flex; flex-direction: column; align-items: center; font-size: 10px; gap: 5px; flex: 1; }
        .mobile-nav-item.active { color: var(--primary); }
        .mobile-nav-item i { font-size: 20px; }

        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding: 25px; gap: 20px; transition: 0.3s; }
        .content-section { display: none; animation: fadeIn 0.3s ease-in-out; }
        .content-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* KPI Cards */
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 5px; }
        .kpi-card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; position: relative; display: flex; flex-direction: column; justify-content: center; }
        .kpi-card h3 { font-size: 12px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        .kpi-card .value { font-size: 28px; font-weight: 700; }
        .kpi-icon { position: absolute; top: 20px; right: 20px; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }

        /* Layout Grid */
        .layout-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 20px; align-items: stretch; }
        .panel { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; }
        .panel-title { font-size: 15px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        #map { height: 480px; width: 100%; border-radius: 8px; }
        .chart-container { flex: 1; min-height: 250px; position: relative; }

        /* Room Grid */
        .room-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 10px; }
        .room-card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; transition: 0.3s; display: flex; flex-direction: column; min-height: 400px; }
        .room-card:hover { transform: translateY(-5px); border-color: var(--primary); box-shadow: 0 12px 24px rgba(0,0,0,0.3); }
        .room-qr-box { background: white; padding: 15px; border-radius: 12px; margin: 20px 0; text-align: center; flex-grow: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .room-actions { display: flex; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 15px; }
        .room-btn { flex: 1; padding: 10px; border-radius: 8px; border: none; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 500; transition: 0.2s; }

        /* Modal Custom Styling */
        .modal-overlay { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; padding: 20px; }
        .modal-content { background: var(--bg-panel); width: 100%; max-width: 450px; border-radius: 16px; border: 1px solid var(--primary); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); position: relative; overflow: hidden; animation: zoomIn 0.2s ease-out; }
        @keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .modal-header { padding: 25px 30px 10px 30px; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 10px 30px 30px 30px; }
        .modal-close { cursor: pointer; font-size: 20px; color: var(--text-muted); transition: 0.2s; }
        
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 8px; }
        .search-input, .status-select { width: 100%; background: var(--bg-dark); border: 1px solid var(--border-color); color: white; padding: 12px 16px; border-radius: 10px; outline: none; transition: 0.3s; font-family: inherit; font-size: 14px; }
        .btn-submit { width: 100%; background: var(--primary); color: white; border: none; padding: 14px; border-radius: 10px; cursor: pointer; font-weight: 600; margin-top: 10px; transition: 0.3s; }

        /* RESPONSIVE BREAKPOINTS */
        @media (max-width: 1024px) {
            .layout-grid { grid-template-columns: 1fr; }
            .sidebar { display: none; }
            .mobile-nav { display: flex; }
            .main-content { padding: 15px; padding-bottom: 100px; }
            #map { height: 350px; }
            .sidebar-brand { display: none; }
        }

        @media (max-width: 600px) {
            .kpi-grid { grid-template-columns: 1fr 1fr; }
            .kpi-card { padding: 15px; }
            .kpi-card .value { font-size: 20px; }
            .room-grid { grid-template-columns: 1fr; }
            .modal-content { width: 95%; padding: 15px; }
        }

        /* Responsive Table */
        .table-container { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 10px; }
        .repair-table { width: 100%; min-width: 700px; border-collapse: separate; border-spacing: 0 8px; font-size: 13px; }
        .repair-table td { padding: 15px; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="https://upload.wikimedia.org/wikipedia/th/thumb/6/6f/RMUTTO_Logo.png/200px-RMUTTO_Logo.png" alt="Logo">
            <div><h1>มทร.ตะวันออก<br><span style="font-weight:normal; font-size:11px; color:var(--text-muted);">พื้นที่บางพระ</span></h1></div>
        </div>
        <ul class="nav-menu">
            <li class="nav-item active" data-target="dashboard-section"><a href="#"><i class="fa-solid fa-chart-pie"></i> ภาพรวม</a></li>
            <li class="nav-item" data-target="repair-list-section"><a href="#"><i class="fa-solid fa-list-ul"></i> รายการแจ้งซ่อม</a></li>
            <li class="nav-item" data-target="building-section"><a href="#"><i class="fa-solid fa-building-circle-check"></i> อาคารและห้อง</a></li>
            <li class="nav-item" data-target="reports-section"><a href="#"><i class="fa-solid fa-file-waveform"></i> รายงานสถิติ</a></li>
        </ul>
        <a href="logout.php" style="margin:25px; color:var(--text-muted); text-decoration:none; font-size:14px;"><i class="fa-solid fa-power-off"></i> ออกจากระบบ</a>
    </aside>

    <nav class="mobile-nav">
        <a href="#" class="mobile-nav-item active" onclick="showSection('dashboard-section')">
            <i class="fa-solid fa-house"></i><span>หน้าแรก</span>
        </a>
        <a href="#" class="mobile-nav-item" onclick="showSection('repair-list-section')">
            <i class="fa-solid fa-screwdriver-wrench"></i><span>งานซ่อม</span>
        </a>
        <a href="#" class="mobile-nav-item" onclick="showSection('building-section')">
            <i class="fa-solid fa-qrcode"></i><span>ตึก/ห้อง</span>
        </a>
        <a href="logout.php" class="mobile-nav-item">
            <i class="fa-solid fa-right-from-bracket"></i><span>ออก</span>
        </a>
    </nav>

    <main class="main-content">
        
        <section id="dashboard-section" class="content-section active">
            <header style="margin-bottom: 25px;">
                <h2 style="font-size:22px;">Dashboard วิเคราะห์การแจ้งซ่อม (Real-time)</h2>
                <p style="font-size:13px; color:var(--text-muted);">ข้อมูลภาพรวมการซ่อมบำรุงในพื้นที่วิทยาเขตบางพระ</p>
            </header>

            <div class="kpi-grid">
                <div class="kpi-card"><h3>แจ้งซ่อมทั้งหมด</h3><div class="value"><?php echo $total_all; ?></div><div class="kpi-icon" style="background:rgba(59,130,246,0.1); color:var(--primary);"><i class="fa-solid fa-wrench"></i></div></div>
                <div class="kpi-card"><h3>เสร็จสิ้นแล้ว</h3><div class="value"><?php echo $total_done; ?></div><div class="kpi-icon" style="background:rgba(16,185,129,0.1); color:var(--success);"><i class="fa-solid fa-check-double"></i></div></div>
                <div class="kpi-card"><h3>รอดำเนินการ</h3><div class="value" style="color:var(--warning);"><?php echo $total_pending; ?></div><div class="kpi-icon" style="background:rgba(245,158,11,0.1); color:var(--warning);"><i class="fa-regular fa-hourglass-half"></i></div></div>
                <div class="kpi-card"><h3>เกิน SLA</h3><div class="value" style="color:var(--danger);"><?php echo $total_sla; ?></div><div class="kpi-icon" style="background:rgba(239,68,68,0.1); color:var(--danger);"><i class="fa-solid fa-circle-exclamation"></i></div></div>
            </div>

            <div class="layout-grid">
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-chart-donut"></i> ประเภทปัญหา</div>
                    <div class="chart-container"><canvas id="pieChart"></canvas></div>
                    <div style="margin-top:30px;">
                        <div class="panel-title" style="font-size:13px; margin-bottom:15px;">แนวโน้มรายสัปดาห์</div>
                        <div style="height:140px;"><canvas id="lineChartTrend"></canvas></div>
                    </div>
                </div>
                <div class="panel" style="padding:0; overflow:hidden;">
                    <div id="map"></div>
                </div>
            </div>
        </section>

        <section id="building-section" class="content-section">
            <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
                <h2 style="font-size:22px; font-weight:700;">บริหารจัดการอาคารและห้อง</h2>
                <button class="btn-submit" style="width:auto; padding: 10px 20px; font-size:13px;" onclick="toggleModal('addRoomModal', true)">
                    <i class="fa-solid fa-plus"></i> เพิ่มห้อง
                </button>
            </header>
            <div class="room-grid">
                <?php $rooms_list->data_seek(0); while($rm = $rooms_list->fetch_assoc()): 
                    $repair_url = "http://".$_SERVER['HTTP_HOST']."/repair_form.php?room_id=".$rm['room_id'];
                    $qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=".urlencode($repair_url);
                ?>
                <div class="room-card">
                    <div style="display:flex; justify-content:space-between;">
                        <div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;"><?php echo $rm['building_name']; ?></div>
                            <h3 style="font-size: 18px; font-weight: 700; color: var(--primary);">ห้อง <?php echo $rm['room_number']; ?></h3>
                            <span style="background: rgba(139, 92, 246, 0.1); color: var(--purple); padding: 2px 8px; border-radius: 6px; font-size: 10px;">ชั้น <?php echo $rm['floor']; ?></span>
                        </div>
                    </div>
                    <div class="room-qr-box">
                        <img src="<?php echo $qr_api; ?>" style="width: 140px;" alt="QR">
                        <a href="<?php echo $qr_api; ?>" download="QR_<?php echo $rm['room_number']; ?>.png" style="margin-top:10px; text-decoration:none; color:var(--text-muted); font-size:11px;"><i class="fa-solid fa-download"></i> Download PNG</a>
                    </div>
                    <div class="room-actions">
                        <button class="room-btn" style="background: rgba(59, 130, 246, 0.1); color: var(--primary);"><i class="fa-solid fa-pen"></i> แก้ไข</button>
                        <button class="room-btn" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);"><i class="fa-solid fa-trash"></i> ลบ</button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </section>

        <section id="repair-list-section" class="content-section">
            <header style="margin-bottom: 25px;"><h2 style="font-size:20px;">รายการแจ้งซ่อมทั้งหมด</h2></header>
            <div class="panel">
                <div class="table-container">
                    <table class="repair-table">
                        <thead><tr><th>วันที่</th><th>หมายเลข</th><th>อาคาร</th><th>ประเภท</th><th>สถานะ</th></tr></thead>
                        <tbody>
                            <?php while($row = $repair_list->fetch_assoc()): ?>
                            <tr style="background:rgba(255,255,255,0.02)">
                                <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                <td><strong>#<?php echo $row['ticket_no']; ?></strong></td>
                                <td><?php echo $row['building_name']; ?></td>
                                <td><?php echo $row['problem_type']; ?></td>
                                <td><span class="status-badge <?php echo ($row['status'] == 'เสร็จสิ้น' ? 'status-success' : 'status-pending'); ?>"><?php echo $row['status']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

    <div id="addRoomModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-size: 20px; font-weight: 700;">เพิ่มข้อมูลห้องใหม่</h3>
                <i class="fa-solid fa-xmark modal-close" onclick="toggleModal('addRoomModal', false)"></i>
            </div>
            <div class="modal-body">
                <form action="save_room.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">เลือกอาคาร</label>
                        <select name="building_id" class="status-select" required>
                            <?php $buildings_dropdown->data_seek(0); while($b = $buildings_dropdown->fetch_assoc()): ?>
                                <option value="<?php echo $b['building_id']; ?>"><?php echo $b['building_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">เลขห้อง / ชื่อห้อง</label>
                        <input type="text" name="room_number" class="search-input" placeholder="เช่น 1502" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ชั้น</label>
                        <input type="number" name="floor" class="search-input" placeholder="ระบุชั้น" required>
                    </div>
                    <button type="submit" class="btn-submit">บันทึกและออกรหัส QR</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // --- Navigation Logic (Support Both Sidebar & Mobile Nav) ---
        function showSection(targetId) {
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-item, .mobile-nav-item').forEach(i => i.classList.remove('active'));
            
            const targetSection = document.getElementById(targetId);
            if(targetSection) {
                targetSection.classList.add('active');
                // Sync desktop nav
                const desktopItem = document.querySelector(`.nav-item[data-target="${targetId}"]`);
                if(desktopItem) desktopItem.classList.add('active');
                // Sync mobile nav
                const mobileItems = document.querySelectorAll('.mobile-nav-item');
                mobileItems.forEach(item => {
                    if(item.getAttribute('onclick')?.includes(targetId)) item.classList.add('active');
                });
                
                localStorage.setItem('lastPage', targetId);
                if(targetId === 'dashboard-section' && window.map) setTimeout(() => map.resize(), 200);
            }
        }

        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                showSection(item.getAttribute('data-target'));
            });
        });

        window.onload = () => showSection(localStorage.getItem('lastPage') || 'dashboard-section');

        function toggleModal(id, show) { document.getElementById(id).style.display = show ? 'flex' : 'none'; }

        // --- Map & Charts ---
        mapboxgl.accessToken = 'pk.eyJ1IjoiYXJtdHZ0aGdhbWVzIiwiYSI6ImNtb2k1dHV1YjBhdXgyc3M5NnM2cjRjdDQifQ.eXpZcMhRLHC7X6JvR9EXxg';
        const map = new mapboxgl.Map({ 
            container: 'map', style: 'mapbox://styles/mapbox/satellite-streets-v12',
            center: [100.9583, 13.2290], zoom: 16.5, pitch: 55, bearing: 5, antialias: true 
        });
        
        map.on('load', function () {
            const layers = map.getStyle().layers;
            const labelLayerId = layers.find(l => l.type === 'symbol' && l.layout['text-field'])?.id;
            map.addLayer({
                'id': '3d-buildings', 'source': 'composite', 'source-layer': 'building', 'filter': ['==', 'extrude', 'true'], 'type': 'fill-extrusion',
                'paint': { 'fill-extrusion-color': '#e2e8f0', 'fill-extrusion-height': ['get', 'height'], 'fill-extrusion-opacity': 0.7 }
            }, labelLayerId);

            const buildings = <?php echo json_encode($map_data); ?>;
            buildings.forEach(b => {
                if (b.coords[0] !== 0) {
                    const el = document.createElement('div');
                    el.style.cssText = `width:14px;height:14px;background:${b.color};border:3px solid white;border-radius:50%;box-shadow:0 0 10px rgba(0,0,0,0.5);cursor:pointer;`;
                    new mapboxgl.Marker(el).setLngLat(b.coords)
                        .setPopup(new mapboxgl.Popup({ offset: 25, closeButton: false })
                        .setHTML(`<div style="font-family:Sarabun;padding:5px;color:#1e293b;"><b style="font-size:13px;">${b.name}</b><br><span style="font-size:11px;">งานค้าง: ${b.count}</span></div>`))
                        .addTo(map);
                }
            });
        });

        // Charts
        Chart.defaults.color = '#94a3b8';
        new Chart(document.getElementById('pieChart'), { type: 'doughnut', data: { labels: <?php echo json_encode($pie_labels); ?>, datasets: [{ data: <?php echo json_encode($pie_values); ?>, backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'], borderWidth: 0, cutout: '75%' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } } });
        new Chart(document.getElementById('lineChartTrend'), { type: 'line', data: { labels: ['W1', 'W2', 'W3', 'W4'], datasets: [{ label: 'แจ้งซ่อม', data: <?php echo json_encode($line_values); ?>, borderColor: '#3b82f6', tension: 0.4, fill: true, backgroundColor: 'rgba(59,130,246,0.1)' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { grid: { display: false } } } } });
    </script>
</body>
</html>