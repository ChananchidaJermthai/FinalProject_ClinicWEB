<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Aura Clinic</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f7f6; }
        .card { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #ff99cc; color: white; }
        .status-pending { color: orange; font-weight: bold; }
        input[type="number"] { width: 70px; padding: 5px; }
        button { padding: 5px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background: #45a049; }
    </style>
</head>
<body>

    <h1>Aura Clinic Backend System</h1>

    <div class="card">
        <h2>📅 รายการนัดหมาย (Appointments)</h2>
        <table>
            <thead>
                <tr>
                    <th>ชื่อลูกค้า</th>
                    <th>บริการ</th>
                    <th>วันที่</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody id="appBody"></tbody>
        </table>
    </div>

    <div class="card">
        <h2>📦 คลังเวชภัณฑ์ (Inventory)</h2>
        <table>
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th>คงเหลือ</th>
                    <th>หน่วย</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody id="stockBody"></tbody>
        </table>
    </div>

    <script>
        
        fetch('/api/appointments')
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('appBody');
                tbody.innerHTML = data.map(app => `
                    <tr>
                        <td>${app.customer_name}</td>
                        <td>${app.service_name}</td>
                        <td>${new Date(app.app_date).toLocaleDateString()}</td>
                        <td class="status-pending">${app.status}</td>
                    </tr>
                `).join('');
            });

        
        function loadStock() {
            fetch('/api/inventory')
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('stockBody');
                    tbody.innerHTML = data.map(item => `
                        <tr>
                            <td>${item.item_name}</td>
                            <td><input type="number" id="qty-${item.id}" value="${item.quantity}"></td>
                            <td>${item.unit}</td>
                            <td><button onclick="updateStock(${item.id})">บันทึก</button></td>
                        </tr>
                    `).join('');
                });
        }

        
        function updateStock(id) {
            const newQty = document.getElementById(`qty-${id}`).value;
            fetch(`/api/stock/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ quantity: parseInt(newQty) })
            })
            .then(res => {
                if(res.ok) {
                    alert('อัปเดตเรียบร้อย!');
                    loadStock();
                }
            });
        }

        loadStock();
    </script>
</body>
</html>