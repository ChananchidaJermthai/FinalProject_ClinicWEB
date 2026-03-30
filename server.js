const express = require('express');
const mysql = require('mysql2');
const cors = require('cors');
const path = require('path'); 
require('dotenv').config();

const app = express();
app.use(cors());
app.use(express.json());

// บอกให้ Express รู้ว่าไฟล์ static (html, css, js) อยู่ที่โฟลเดอร์ปัจจุบัน
app.use(express.static(path.join(__dirname, './')));

const db = mysql.createConnection({
    host: process.env.DB_HOST,
    port: process.env.DB_PORT || 3306, 
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
});

db.connect((err) => {
    if (err) {
        console.error('❌ Error connecting to the database:', err);
        return;
    }
    console.log('✅ Connected to MySQL Database on Cloud');
});

// --- ส่วนจัดการเส้นทางไฟล์หน้าเว็บ (Routing) ---
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

app.get('/admin', (req, res) => {
    res.sendFile(path.join(__dirname, 'admin.html'));
});

// --- API สำหรับดึงข้อมูล ---

// ดึงรายการนัดหมาย
app.get('/api/appointments', (req, res) => {
    db.query('SELECT * FROM appointments', (err, result) => {
        if (err) return res.status(500).send(err);
        res.json(result);
    });
});

// ดึงรายการลูกค้า
app.get('/api/customers', (req, res) => {
    db.query('SELECT * FROM customers', (err, result) => {
        if (err) return res.status(500).send(err);
        res.json(result);
    });
});

app.get('/api/inventory', (req, res) => {
    db.query('SELECT * FROM inventory', (err, result) => {
        if (err) return res.status(500).send(err);
        res.json(result);
    });
});

// อัปเดตสต็อกสินค้า
app.put('/api/stock/:id', (req, res) => {
    const { quantity } = req.body;
    const { id } = req.params;
    
    db.query('UPDATE inventory SET quantity = ? WHERE id = ?', [quantity, id], (err) => {
        if (err) return res.status(500).send(err);

        const logMsg = `Updated stock ID: ${id} to ${quantity}`;
        db