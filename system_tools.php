<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Aura Clinic Admin System</title>
  <style>
    :root {
      --pink: #e875b8;
      --pink-dark: #d25a9e;
      --bg: #f7f7fb;
      --text: #222;
      --muted: #666;
      --border: #e5e7eb;
      --danger: #dc2626;
      --success: #0f9d58;
      --warning: #d97706;
      --info: #2563eb;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: var(--text); background: var(--bg); }
    .container { max-width: 1200px; margin: 0 auto; padding: 24px; }
    .card { background: #fff; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); padding: 20px; margin-bottom: 20px; }
    .login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
    .login-card { width: 100%; max-width: 420px; }
    h1,h2,h3 { margin-top: 0; }
    .muted { color: var(--muted); }
    .grid { display: grid; gap: 16px; }
    .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .field { display: flex; flex-direction: column; gap: 8px; margin-bottom: 14px; }
    label { font-weight: 600; }
    input,select,textarea,button { font: inherit; }
    input,select,textarea { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: #fff; }
    textarea { min-height: 90px; resize: vertical; }
    button { border: none; border-radius: 10px; padding: 10px 14px; cursor: pointer; transition: 0.2s ease; }
    button:hover { transform: translateY(-1px); }
    .btn-primary { background: var(--pink); color: white; }
    .btn-primary:hover { background: var(--pink-dark); }
    .btn-secondary { background: #eef2ff; color: #333; }
    .btn-danger { background: #fee2e2; color: var(--danger); }
    .btn-success { background: #dcfce7; color: #166534; }
    .btn-warning { background: #fff7ed; color: var(--warning); }
    .btn-info { background: #dbeafe; color: var(--info); }
    .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .toolbar .actions, .toolbar .filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    table { width: 100%; border-collapse: collapse; overflow: hidden; }
    th, td { border-bottom: 1px solid var(--border); padding: 12px 10px; text-align: left; vertical-align: top; }
    th { background: #fce7f3; color: #7a2154; }
    .table-wrap { overflow-x: auto; }
    .status-badge { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 13px; font-weight: 700; text-transform: capitalize; }
    .status-pending { background: #fff7ed; color: var(--warning); }
    .status-confirmed { background: #ecfeff; color: #0f766e; }
    .status-completed { background: #ecfdf5; color: var(--success); }
    .status-cancelled { background: #fef2f2; color: var(--danger); }
    .summary-box { background: linear-gradient(135deg, #fff1f8, #ffffff); border: 1px solid #f5d0e3; border-radius: 16px; padding: 18px; }
    .summary-number { font-size: 28px; font-weight: 800; margin-top: 8px; }
    .topbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
    .user-badge { padding: 10px 14px; border-radius: 999px; background: #fff; box-shadow: 0 4px 16px rgba(0,0,0,0.07); }
    .message { padding: 12px 14px; border-radius: 10px; margin-bottom: 14px; display: none; }
    .message.error { display: block; background: #fef2f2; color: #991b1b; }
    .message.success { display: block; background: #ecfdf5; color: #166534; }
    .inline-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .help-box { padding: 14px 16px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 16px; line-height: 1.6; }
    .restore-box { border: 1px dashed #cbd5e1; border-radius: 12px; padding: 16px; background: #fcfcff; }
    .hidden { display: none !important; }
    .small { font-size: 13px; }
    @media (max-width: 900px) { .grid-2, .grid-4 { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <section id="loginSection" class="login-wrap hidden">
    <div class="card login-card">
      <h1>🔐 Aura Clinic Admin Login</h1>
      <p class="muted">เข้าสู่ระบบเพื่อจัดการคลังสินค้า รายการนัดหมาย และข้อมูลลูกค้า</p>
      <div id="loginMessage" class="message"></div>
      <form id="loginForm">
        <div class="field">
          <label for="username">ชื่อผู้ใช้</label>
          <input id="username" name="username" placeholder="เช่น superadmin" required />
        </div>
        <div class="field" id="passwordField">
          <label for="password">รหัสผ่าน</label>
          <input id="password" name="password" type="password" placeholder="กรอกรหัสผ่าน" required />
        </div>
        <div class="field hidden" id="otpField">
          <label for="otp">รหัส OTP (ตรวจสอบจาก Log หรือ Console)</label>
          <div style="display:flex; gap: 8px;">
            <input id="otp" name="otp" type="text" placeholder="กรอก OTP 6 หลัก" style="flex:1;" />
            <button class="btn-secondary" type="button" id="resendOtpBtn" disabled>ส่งใหม่อีกครั้ง</button>
          </div>
        </div>
        <button class="btn-primary" type="submit" id="loginSubmitBtn">เข้าสู่ระบบ</button>
        <button class="btn-secondary hidden" type="button" id="loginBackBtn" style="margin-top: 8px;">กลับ</button>
      </form>
      <p class="muted small" style="margin-top: 12px;">ค่าเริ่มต้น: superadmin / super123</p>
    </div>
  </section>

  <main id="appSection" class="hidden">
    <div class="container">
      <div class="topbar">
        <div>
          <h1>⚙️ System Tools & Logs</h1>
          <p class="muted">สำหรับ System Admin จัดการสำรองกู้คืนข้อมูล และดูประวัติการทำรายการ</p>
        </div>
        <div class="actions" style="display:flex; align-items:center; gap: 16px;">
          <a href="admin.php" class="btn-info" style="text-decoration:none; padding:12px 20px; border-radius:12px; font-weight:700; background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 4px 12px rgba(5,150,105,0.3); transition: transform 0.2s; display:inline-block; font-size: 15px;">🏠 กลับหน้าหลัก</a>
          <div id="currentUser" class="user-badge">กำลังโหลดผู้ใช้...</div>
          <button id="logoutBtn" class="btn-secondary" type="button">ออกจากระบบ</button>
        </div>
      </div>

      <div id="appMessage" class="message"></div>



      <section class="card">
        <div class="toolbar">
          <div>
            <h2>💾 Backup / Restore ข้อมูลระบบ</h2>
            <p class="muted">สำรองข้อมูลระบบเป็นไฟล์ JSON และกู้คืนข้อมูลกลับเข้าระบบเมื่อเกิดปัญหา</p>
          </div>
          <div class="actions">
            <button id="downloadBackupBtn" class="btn-info" type="button">ดาวน์โหลดไฟล์สำรอง</button>
          </div>
        </div>

        <div class="help-box small">
          <strong>คำแนะนำ:</strong>
          ระบบนี้เป็น <strong>Manual Backup / Restore</strong> คือผู้ดูแลระบบกดสำรองข้อมูลเองเป็นไฟล์ และใช้ไฟล์นั้นกู้คืนภายหลังได้
          โดยเมื่อกู้คืน ระบบจะ <strong>แทนที่ข้อมูลเดิมทั้งหมด</strong> ใน users, inventory, appointments และ staff_logs
        </div>

        <div class="restore-box">
          <div class="field">
            <label for="backupFileInput">เลือกไฟล์สำรองข้อมูล (.enc)</label>
            <input id="backupFileInput" type="file" accept=".enc" />
          </div>
          <div class="inline-actions">
            <button id="restoreBackupBtn" class="btn-danger" type="button">กู้คืนข้อมูลจากไฟล์</button>
          </div>
          <p class="muted small" style="margin-top: 12px;">ก่อนกู้คืน แนะนำให้ดาวน์โหลดไฟล์สำรองปัจจุบันเก็บไว้ก่อนทุกครั้ง</p>
        </div>
      </section>

      <section class="card">
        <div class="toolbar">
          <div>
            <h2>📝 ประวัติการทำรายการล่าสุด</h2>
            <p class="muted">แสดงการเข้าสู่ระบบ เพิ่ม/แก้ไข/ลบข้อมูล อัปเดต stock และการสำรอง/กู้คืนข้อมูล</p>
          </div>
          <div class="filters">
            <input id="logSearchId" type="number" placeholder="ค้นหา KeyResult(id)" style="width:150px;" />
            <input id="logSearchUsername" placeholder="ค้นหาชื่อผู้ใช้งาน" style="width:150px;" />
            <input id="logSearchDate" type="date" />
            <button class="btn-secondary" id="logSearchBtn" type="button">ค้นหา</button>
            <button class="btn-secondary" id="logClearBtn" type="button">ล้าง</button>
            <button id="refreshLogsBtn" class="btn-secondary" type="button">รีเฟรช</button>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>เวลา</th><th>ชื่อผู้ใช้งาน</th><th>Admin Role</th><th>รายการ</th><th>KeyResult(id)</th></tr></thead>
            <tbody id="logsBody"></tbody>
          </table>
        </div>
      </section>

      <section class="card">
        <div class="toolbar">
          <div>
            <h2>👥 จัดการผู้ใช้งาน (UserAdmin)</h2>
            <p class="muted">สร้างและแก้ไขข้อมูล UserAdmin (Username, Password, เบอร์โทรศัพท์)</p>
          </div>
        </div>
        <form id="userForm">
          <input type="hidden" id="userId" />
          <div class="grid grid-4">
            <div class="field"><label for="userFullname">ชื่อ-นามสกุล</label><input id="userFullname" required /></div>
            <div class="field"><label for="userUsername">Username</label><input id="userUsername" required /></div>
            <div class="field"><label for="userPassword">Password</label><input id="userPassword" type="password" placeholder="เว้นว่างถ้าไม่เปลี่ยน" /></div>
            <div class="field"><label for="userPhone">เบอร์โทรศัพท์</label><input id="userPhone" /></div>
          </div>
          <div class="inline-actions">
            <button class="btn-primary" type="submit" id="userSubmitBtn">เพิ่มผู้ใช้งาน</button>
            <button class="btn-secondary hidden" type="button" id="userCancelEditBtn">ยกเลิกการแก้ไข</button>
          </div>
        </form>
        <div class="table-wrap" style="margin-top:16px;">
          <table>
            <thead><tr><th>ชื่อ-นามสกุล</th><th>Username</th><th>เบอร์โทรศัพท์</th><th>บทบาท</th><th>จัดการ</th></tr></thead>
            <tbody id="usersBody"></tbody>
          </table>
        </div>
      </section>
    </div>
  </main>

  <script>
    const API = {
      login: 'api/auth_login.php',
      me: 'api/auth_me.php',
      logout: 'api/auth_logout.php',
      summary: 'api/dashboard_summary.php',
      inventory: 'api/inventory.php',
      stockUpdate: 'api/stock_update.php',
      appointments: 'api/appointments.php',
      logs: 'api/staff_logs.php',
      backup: 'api/system_backup.php',
      restore: 'api/system_restore.php',
      users: 'api/users.php',
      verifyOtp: 'api/auth_verify_otp.php'
    };

    let currentUser = null;
    let appointmentCache = [];
    let inventoryCache = [];

    const loginSection = document.getElementById('loginSection');
    const appSection = document.getElementById('appSection');
    const loginMessage = document.getElementById('loginMessage');
    const appMessage = document.getElementById('appMessage');
    const currentUserEl = document.getElementById('currentUser');
    const backupFileInput = document.getElementById('backupFileInput');

    function showMessage(target, text, type = 'success') {
      target.className = `message ${type}`;
      target.textContent = text;
      target.style.display = 'block';
      clearTimeout(target._timer);
      target._timer = setTimeout(() => {
        target.style.display = 'none';
      }, 4000);
    }

    function hideMessage(target) {
      target.style.display = 'none';
    }

    async function apiFetch(url, options = {}) {
      const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          ...(options.headers || {})
        },
        ...options
      });

      let data = null;
      try {
        data = await response.json();
      } catch (_) {}

      if (!response.ok) {
        throw new Error(data?.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อระบบ');
      }

      return data;
    }

    async function apiDownload(url) {
      const response = await fetch(url, { credentials: 'same-origin' });

      if (!response.ok) {
        let data = null;
        try { data = await response.json(); } catch (_) {}
        throw new Error(data?.message || 'ไม่สามารถดาวน์โหลดไฟล์สำรองได้');
      }

      const blob = await response.blob();
      const disposition = response.headers.get('content-disposition') || '';
      const match = disposition.match(/filename="?([^";]+)"?/i);
      const filename = match ? match[1] : 'aura_clinic_backup.enc';

      const downloadUrl = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(downloadUrl);
    }

    function formatDateTime(value) {
      if (!value) return '-';
      return new Date(value).toLocaleString('th-TH');
    }

    function formatDateTimeLocal(value) {
      if (!value) return '';
      const date = new Date(value);
      const tzOffset = date.getTimezoneOffset() * 60000;
      return new Date(date.getTime() - tzOffset).toISOString().slice(0, 16);
    }

    function renderAppMode(isLoggedIn) {
      loginSection.classList.toggle('hidden', isLoggedIn);
      appSection.classList.toggle('hidden', !isLoggedIn);
    }



    async function loadLogs() {
      const qId = document.getElementById('logSearchId').value.trim();
      const qUsername = document.getElementById('logSearchUsername').value.trim();
      const qDate = document.getElementById('logSearchDate').value;

      const params = new URLSearchParams();
      if (qId) params.append('id', qId);
      if (qUsername) params.append('username', qUsername);
      if (qDate) params.append('date', qDate);

      const data = await apiFetch(`${API.logs}?${params.toString()}`);
      const tbody = document.getElementById('logsBody');
      tbody.innerHTML = data.length ? data.map(log => `
        <tr>
          <td>${formatDateTime(log.created_at)}</td>
          <td>${log.staff_name}</td>
          <td>${log.admin_role}</td>
          <td>${log.action_text}</td>
          <td>${log.key_result_id || '-'}</td>
        </tr>
      `).join('') : '<tr><td colspan="5" class="muted">ยังไม่มีประวัติการทำรายการ</td></tr>';
    }

    let usersCache = [];
    async function loadUsers() {
      const data = await apiFetch(API.users);
      usersCache = data;
      const tbody = document.getElementById('usersBody');
      tbody.innerHTML = data.length ? data.map(u => `
        <tr>
          <td>${u.full_name}</td>
          <td>${u.username}</td>
          <td>${u.phone || '-'}</td>
          <td>${u.role}</td>
          <td>
            <button type="button" class="btn-warning" onclick="editUser(${u.id})">แก้ไข</button>
            <button type="button" class="btn-danger" onclick="deleteUser(${u.id}, '${u.username}')">ลบ</button>
          </td>
        </tr>
      `).join('') : '<tr><td colspan="5" class="muted">ไม่พบข้อมูลผู้ใช้งาน</td></tr>';
    }

    function editUser(id) {
      const u = usersCache.find(x => Number(x.id) === Number(id));
      if(!u) return;
      document.getElementById('userId').value = u.id;
      document.getElementById('userFullname').value = u.full_name;
      document.getElementById('userUsername').value = u.username;
      document.getElementById('userPhone').value = u.phone || '';
      document.getElementById('userPassword').value = '';
      document.getElementById('userSubmitBtn').textContent = 'บันทึกการแก้ไข';
      document.getElementById('userCancelEditBtn').classList.remove('hidden');
    }

    async function deleteUser(id, username) {
      if(!confirm(`ยืนยันการลบผู้ใช้ ${username}?`)) return;
      try {
        await apiFetch(API.users, { method: 'DELETE', body: JSON.stringify({ id }) });
        showMessage(appMessage, 'ลบผู้ใช้เรียบร้อย');
        await loadUsers();
      } catch(e) {
        showMessage(appMessage, e.message, 'error');
      }
    }

    document.getElementById('userCancelEditBtn')?.addEventListener('click', () => {
      document.getElementById('userForm').reset();
      document.getElementById('userId').value = '';
      document.getElementById('userSubmitBtn').textContent = 'เพิ่มผู้ใช้งาน';
      document.getElementById('userCancelEditBtn').classList.add('hidden');
    });

    document.getElementById('userForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const id = document.getElementById('userId').value;
        const payload = {
          id: id ? Number(id) : 0,
          full_name: document.getElementById('userFullname').value.trim(),
          username: document.getElementById('userUsername').value.trim(),
          password: document.getElementById('userPassword').value,
          phone: document.getElementById('userPhone').value.trim(),
          role: 'admin'
        };
        await apiFetch(API.users, {
          method: id ? 'PUT' : 'POST',
          body: JSON.stringify(payload)
        });
        showMessage(appMessage, id ? 'แก้ไขผู้ใช้เรียบร้อย' : 'เพิ่มผู้ใช้เรียบร้อย');
        document.getElementById('userCancelEditBtn').click();
        await loadUsers();
      } catch(err) {
        showMessage(appMessage, err.message, 'error');
      }
    });

    document.getElementById('logSearchBtn')?.addEventListener('click', loadLogs);
    document.getElementById('logClearBtn')?.addEventListener('click', () => {
      document.getElementById('logSearchId').value = '';
      document.getElementById('logSearchUsername').value = '';
      document.getElementById('logSearchDate').value = '';
      loadLogs();
    });



    async function downloadBackup() {
      try {
        await apiDownload(API.backup);
        showMessage(appMessage, 'ดาวน์โหลดไฟล์สำรองข้อมูลเรียบร้อย');
        await loadLogs();
      } catch (error) {
        showMessage(appMessage, error.message, 'error');
      }
    }

    async function restoreBackup() {
      const file = backupFileInput.files[0];
      if (!file) {
        showMessage(appMessage, 'กรุณาเลือกไฟล์สำรองข้อมูลก่อน', 'error');
        return;
      }

      if (!confirm('การกู้คืนข้อมูลจะเขียนทับข้อมูลปัจจุบันทั้งหมดในระบบ\n\nต้องการดำเนินการต่อหรือไม่?')) {
        return;
      }

      try {
        const text = await file.text();
        await apiFetch(API.restore, {
          method: 'POST',
          body: JSON.stringify({ encrypted_backup: text })
        });
        backupFileInput.value = '';
        showMessage(appMessage, 'กู้คืนข้อมูลระบบเรียบร้อย');
        await refreshAll();
      } catch (error) {
        showMessage(appMessage, error.message || 'ไม่สามารถกู้คืนข้อมูลได้', 'error');
      }
    }

    async function refreshAll() {
      await loadLogs();
      await loadUsers();
    }

    let isOtpMode = false;
    let resendTimer = null;

    function startResendTimer() {
      const btn = document.getElementById('resendOtpBtn');
      if (!btn) return;
      btn.disabled = true;
      let timeLeft = 60;
      btn.textContent = `รอ ${timeLeft}s`;
      clearInterval(resendTimer);
      resendTimer = setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) {
          clearInterval(resendTimer);
          btn.textContent = 'ส่งใหม่อีกครั้ง';
          btn.disabled = false;
        } else {
          btn.textContent = `รอ ${timeLeft}s`;
        }
      }, 1000);
    }

    document.getElementById('resendOtpBtn')?.addEventListener('click', async () => {
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      try {
        const data = await apiFetch(API.login, {
          method: 'POST',
          body: JSON.stringify({ username, password })
        });
        if(data.require_otp) {
          showMessage(loginMessage, data.message, 'success');
          startResendTimer();
        }
      } catch (error) {
        showMessage(loginMessage, error.message, 'error');
      }
    });

    document.getElementById('loginForm').addEventListener('submit', async (event) => {
      event.preventDefault();
      hideMessage(loginMessage);
      try {
        if(!isOtpMode) {
          const username = document.getElementById('username').value.trim();
          const password = document.getElementById('password').value;
          const data = await apiFetch(API.login, {
            method: 'POST',
            body: JSON.stringify({ username, password })
          });
          if(data.require_otp) {
             isOtpMode = true;
             document.getElementById('passwordField').classList.add('hidden');
             document.getElementById('otpField').classList.remove('hidden');
             document.getElementById('loginSubmitBtn').textContent = 'ยืนยัน OTP';
             document.getElementById('loginBackBtn').classList.remove('hidden');
             showMessage(loginMessage, data.message, 'success');
             startResendTimer();
          }
        } else {
          const otp = document.getElementById('otp').value.trim();
          const data = await apiFetch(API.verifyOtp, {
            method: 'POST',
            body: JSON.stringify({ otp })
          });
          currentUser = data.user;
          if (currentUser.role !== 'superadmin') {
            await apiFetch(API.logout, { method: 'POST' });
            throw new Error('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
          }
          currentUserEl.textContent = `${currentUser.full_name} (${currentUser.role})`;
          renderAppMode(true);
          showMessage(appMessage, 'เข้าสู่ระบบสำเร็จ');
          resetLoginForm();
          await refreshAll();
        }
      } catch (error) {
        showMessage(loginMessage, error.message, 'error');
      }
    });

    function resetLoginForm() {
       isOtpMode = false;
       document.getElementById('passwordField').classList.remove('hidden');
       document.getElementById('otpField').classList.add('hidden');
       document.getElementById('loginSubmitBtn').textContent = 'เข้าสู่ระบบ';
       document.getElementById('loginBackBtn').classList.add('hidden');
       document.getElementById('loginForm').reset();
       clearInterval(resendTimer);
    }

    document.getElementById('loginBackBtn').addEventListener('click', () => {
       resetLoginForm();
       hideMessage(loginMessage);
    });

    document.getElementById('logoutBtn').addEventListener('click', async () => {
      try { await apiFetch(API.logout, { method: 'POST' }); } catch (_) {}
      currentUser = null;
      renderAppMode(false);
      document.getElementById('loginForm').reset();
      showMessage(loginMessage, 'ออกจากระบบเรียบร้อย');
    });

    document.getElementById('refreshLogsBtn').addEventListener('click', loadLogs);
    document.getElementById('downloadBackupBtn').addEventListener('click', downloadBackup);
    document.getElementById('restoreBackupBtn').addEventListener('click', restoreBackup);



    async function boot() {

      try {
        const data = await apiFetch(API.me);
        currentUser = data.user;
        currentUserEl.textContent = `${currentUser.full_name} (${currentUser.role})`;
        if (currentUser.role !== 'superadmin') {
          alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
          window.location.href = 'admin.php';
          return;
        }
        renderAppMode(true);
        await refreshAll();
      } catch (_) {
        renderAppMode(false);
      }
    }

    boot();

  </script>
</body>
</html>
