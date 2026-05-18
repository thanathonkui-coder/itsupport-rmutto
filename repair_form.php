<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FixIt - ระบบแจ้งซ่อม IT Support ออนไลน์</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-body: #f1f5f9;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Sarabun', sans-serif; }
        body { background-color: var(--bg-body); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }

        .container {
            background: white;
            width: 100%;
            max-width: 1100px;
            display: flex;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        }

        /* ฝั่งซ้าย (Sidebar) */
        .sidebar {
            width: 35%;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sidebar-header i { font-size: 40px; margin-bottom: 20px; }
        .sidebar-header h1 { font-size: 32px; font-weight: 700; line-height: 1.2; margin-bottom: 15px; }
        .sidebar-header p { opacity: 0.8; font-weight: 300; line-height: 1.6; }

        .steps { list-style: none; margin-top: 50px; }
        .step-item { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; opacity: 0.6; transition: 0.3s; }
        .step-item.active { opacity: 1; }
        .step-num { width: 32px; height: 32px; border: 2px solid white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; }

        /* ฝั่งขวา (Form) */
        .form-section { width: 65%; padding: 60px; background: white; }

        .grid-row { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }

        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 14px; font-weight: 600; color: var(--primary); }
        .form-control {
            width: 100%;
            padding: 12px 0;
            border: none;
            border-bottom: 2px solid var(--border);
            outline: none;
            font-size: 15px;
            transition: 0.3s;
            background: transparent;
        }
        .form-control:focus { border-color: var(--primary); }

        /* Priority Buttons */
        .priority-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 10px; }
        .priority-btn {
            padding: 10px;
            border: 1px solid var(--border);
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            color: var(--text-muted);
            transition: 0.2s;
            text-align: center;
        }
        .priority-btn:hover { background: #f8fafc; }
        .priority-btn.active { background: #eef2ff; border-color: var(--primary); color: var(--primary); font-weight: 600; }

        /* Image Upload */
        .upload-area {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-top: 10px;
            cursor: pointer;
            transition: 0.3s;
        }
        .upload-area:hover { border-color: var(--primary); background: #f8fafc; }
        .upload-area i { font-size: 30px; color: var(--text-muted); margin-bottom: 10px; }
        .upload-area span { display: block; font-size: 12px; color: var(--text-muted); }

        .footer-actions { display: flex; justify-content: flex-end; align-items: center; gap: 20px; margin-top: 40px; }
        .btn-reset { color: var(--text-muted); text-decoration: none; font-size: 14px; cursor: pointer; }
        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
            transition: 0.3s;
        }
        .btn-submit:hover { background: var(--primary-hover); transform: translateY(-2px); }

        @media (max-width: 900px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; padding: 40px; }
            .form-section { width: 100%; padding: 40px; }
            .grid-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-screwdriver-wrench"></i>
            <h1>แบบฟอร์ม<br>แจ้งซ่อมออนไลน์</h1>
            <p>กรุณากรอกข้อมูลให้ครบถ้วน เพื่อความรวดเร็วในการประสานงานโดยทีมช่าง IT ผู้เชี่ยวชาญ</p>
            
            <ul class="steps">
                <li class="step-item active"><div class="step-num">01</div> ข้อมูลผู้แจ้ง</li>
                <li class="step-item"><div class="step-num">02</div> รายละเอียดปัญหา</li>
                <li class="step-item"><div class="step-num">03</div> ตรวจสอบและส่ง</li>
            </ul>
        </div>
        <div style="font-size: 12px; opacity: 0.6;">* ข้อมูลของคุณจะถูกเก็บเป็นความลับ</div>
    </div>

    <div class="form-section">
        <form id="repairForm" action="process_repair.php" method="POST" enctype="multipart/form-data">
            
            <div class="grid-row">
                <div class="form-group">
                    <label>ชื่อผู้แจ้งซ่อม</label>
                    <input type="text" name="reporter_name" class="form-control" placeholder="เช่น สมชาย ใจดี" required>
                </div>
                <div class="form-group">
                    <label>เบอร์โทรศัพท์ติดต่อ</label>
                    <input type="text" name="reporter_phone" class="form-control" placeholder="08x-xxx-xxxx" required>
                </div>
            </div>

            <div class="grid-row">
                <div class="form-group">
                    <label>อาคาร / สถานที่</label>
                    <input type="text" name="location" class="form-control" placeholder="เช่น ห้อง 402 หรือ อาคาร A" required>
                </div>
                <div class="form-group">
                    <label>ประเภทงานซ่อม (IT Support)</label>
                    <select name="problem_type" class="form-control" required>
                        <option value="" disabled selected>เลือกประเภทงาน</option>
                        <optgroup label="ฮาร์ดแวร์">
                            <option value="คอมพิวเตอร์">คอมพิวเตอร์ / Notebook</option>
                            <option value="จอภาพ">จอภาพ (Monitor)</option>
                            <option value="เครื่องพิมพ์">เครื่องพิมพ์ (Printer / Scanner)</option>
                            <option value="อุปกรณ์ต่อพ่วง">เมาส์ / คีย์บอร์ด / อุปกรณ์ต่อพ่วง</option>
                        </optgroup>
                        <optgroup label="เน็ตเวิร์ก & ระบบ">
                            <option value="อินเทอร์เน็ต">อินเทอร์เน็ต (Wi-Fi / LAN)</option>
                            <option value="ระบบบัญชี">อีเมล / รหัสผ่าน (Login)</option>
                            <option value="โทรศัพท์">โทรศัพท์ภายใน / IP Phone</option>
                        </optgroup>
                        <optgroup label="ซอฟต์แวร์">
                            <option value="ติดตั้งโปรแกรม">ติดตั้ง / อัปเดตโปรแกรม</option>
                            <option value="ไวรัส">ไวรัส / มัลแวร์</option>
                            <option value="Windows">ระบบปฏิบัติการ (Windows / macOS)</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label>ความเร่งด่วน</label>
                <div class="priority-container">
                    <input type="hidden" name="priority" id="priority_val" value="ปกติ">
                    <div class="priority-btn active" onclick="setPriority('ปกติ', this)">ปกติ</div>
                    <div class="priority-btn" onclick="setPriority('ปานกลาง', this)">ปานกลาง</div>
                    <div class="priority-btn" onclick="setPriority('ด่วน', this)">ด่วน</div>
                    <div class="priority-btn" onclick="setPriority('ด่วนที่สุด', this)">ด่วนที่สุด</div>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label>รายละเอียดของปัญหา</label>
                <textarea name="description" class="form-control" rows="3" placeholder="กรุณาระบุรายละเอียด เช่น เปิดเครื่องไม่ติด, พิมพ์ไม่ออก..." style="border: 1px solid var(--border); border-radius: 8px; padding: 15px;" required></textarea>
            </div>

            <div class="form-group">
                <label>รูปภาพประกอบ</label>
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <p style="font-size: 14px; font-weight: 500;">คลิกเพื่อเพิ่มรูปภาพ</p>
                    <span>ไฟล์ JPG, PNG (สูงสุด 3 รูป)</span>
                    <input type="file" id="fileInput" name="images[]" multiple hidden accept="image/*">
                </div>
            </div>

            <div class="footer-actions">
                <span class="btn-reset" onclick="document.getElementById('repairForm').reset()">ล้างข้อมูล</span>
                <button type="submit" class="btn-submit">ส่งแจ้งซ่อม</button>
            </div>

        </form>
    </div>
</div>

<script>
    function setPriority(level, el) {
        document.getElementById('priority_val').value = level;
        const btns = document.querySelectorAll('.priority-btn');
        btns.forEach(btn => btn.classList.remove('active'));
        el.classList.add('active');
    }

    // ตัวอย่างการแสดงผลหลังจากส่งฟอร์ม (จำลอง)
    document.getElementById('repairForm').onsubmit = function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'บันทึกข้อมูลสำเร็จ!',
            text: 'ระบบได้รับเรื่องแจ้งซ่อมของคุณเรียบร้อยแล้ว',
            icon: 'success',
            confirmButtonColor: '#4f46e5'
        }).then(() => {
            // ส่งค่าจริงไปยัง process_repair.php หรืออื่นๆ
            // this.submit(); 
        });
    }
</script>

</body>
</html>