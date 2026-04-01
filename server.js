const express = require('express');
const mysql = require('mysql2/promise');
const cors = require('cors');
const path = require('path');
const crypto = require('crypto');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;
const SESSION_TTL_MS = 1000 * 60 * 60 * 8; // 8 ชั่วโมง

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname)));

const pool = mysql.createPool({
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT || 3306),
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
});

const sessions = new Map();

function hashPassword(password) {
    return crypto.createHash('sha256').update(password).digest('hex');
}

function createToken() {
    return crypto.randomBytes(32).toString('hex');
}

function normalizeDateTime(value) {
    if (!value) return null;
    return value.replace('T', ' ');
}

async function logAction(staffName, actionText) {
    try {
        await pool.execute(
            'INSERT INTO staff_logs (staff_name, action_text) VALUES (?, ?)',
            [staffName, actionText]
        );
    } catch (error) {
        console.error('Log Error:', error.message);
    }
}

function getTokenFromRequest(req) {
    const authHeader = req.headers.authorization || '';
    if (authHeader.startsWith('Bearer ')) {
        return authHeader.slice(7);
    }
    return req.headers['x-auth-token'];
}

function requireAuth(req, res, next) {
    const token = getTokenFromRequest(req);

    if (!token || !sessions.has(token)) {
        return res.status(401).json({ message: 'กรุณาเข้าสู่ระบบก่อนใช้งาน' });
    }

    const session = sessions.get(token);

    if (Date.now() > session.expiresAt) {
        sessions.delete(token);
        return res.status(401).json({ message: 'Session หมดอายุ กรุณาเข้าสู่ระบบใหม่' });
    }

    session.expiresAt = Date.now() + SESSION_TTL_MS;
    sessions.set(token, session);

    req.user = session.user;
    req.token = token;
    next();
}

async function queryOne(sql, params = []) {
    const [rows] = await pool.execute(sql, params);
    return rows[0] || null;
}

async function queryMany(sql, params = []) {
    const [rows] = await pool.execute(sql, params);
    return rows;
}

app.get('/', (req, res) => res.sendFile(path.join(__dirname, 'admin.html')));
app.get('/admin', (req, res) => res.sendFile(path.join(__dirname, 'admin.html')));

// =========================
// AUTH
// =========================
app.post('/api/auth/login', async (req, res) => {
    try {
        const { username, password } = req.body;

        if (!username || !password) {
            return res.status(400).json({ message: 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน' });
        }

        const user = await queryOne(
            'SELECT id, username, password_hash, full_name, role, is_active FROM users WHERE username = ? LIMIT 1',
            [username]
        );

        if (!user || !user.is_active) {
            return res.status(401).json({ message: 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง' });
        }

        if (user.password_hash !== hashPassword(password)) {
            return res.status(401).json({ message: 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง' });
        }

        const token = createToken();
        const sessionUser = {
            id: user.id,
            username: user.username,
            full_name: user.full_name,
            role: user.role,
        };

        sessions.set(token, {
            user: sessionUser,
            expiresAt: Date.now() + SESSION_TTL_MS,
        });

        await logAction(sessionUser.full_name, `เข้าสู่ระบบ (${sessionUser.username})`);

        res.json({ token, user: sessionUser });
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'ไม่สามารถเข้าสู่ระบบได้' });
    }
});

app.get('/api/auth/me', requireAuth, async (req, res) => {
    res.json({ user: req.user });
});

app.post('/api/auth/logout', requireAuth, async (req, res) => {
    sessions.delete(req.token);
    await logAction(req.user.full_name, `ออกจากระบบ (${req.user.username})`);
    res.json({ message: 'ออกจากระบบเรียบร้อย' });
});

// =========================
// DASHBOARD SUMMARY
// =========================
app.get('/api/dashboard/summary', requireAuth, async (req, res) => {
    try {
        const totalAppointments = await queryOne('SELECT COUNT(*) AS count FROM appointments');
        const pendingAppointments = await queryOne("SELECT COUNT(*) AS count FROM appointments WHERE status = 'pending'");
        const totalInventoryItems = await queryOne('SELECT COUNT(*) AS count FROM inventory');
        const lowStockItems = await queryOne('SELECT COUNT(*) AS count FROM inventory WHERE quantity <= min_quantity');

        res.json({
            totalAppointments: totalAppointments.count,
            pendingAppointments: pendingAppointments.count,
            totalInventoryItems: totalInventoryItems.count,
            lowStockItems: lowStockItems.count,
        });
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'ไม่สามารถดึงข้อมูลสรุปได้' });
    }
});

// =========================
// INVENTORY
// =========================
app.get('/api/inventory', requireAuth, async (req, res) => {
    try {
        const q = (req.query.q || '').trim();
        const onlyLowStock = req.query.lowStock === 'true';

        let sql = 'SELECT * FROM inventory WHERE 1=1';
        const params = [];

        if (q) {
            sql += ' AND (item_name LIKE ? OR unit LIKE ?)';
            params.push(`%${q}%`, `%${q}%`);
        }

        if (onlyLowStock) {
            sql += ' AND quantity <= min_quantity';
        }

        sql += ' ORDER BY item_name ASC';

        const rows = await queryMany(sql, params);
        res.json(rows);
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'ไม่สามารถดึงข้อมูลคลังสินค้าได้' });
    }
});

app.post('/api/inventory', requireAuth, async (req, res) => {
    try {
        const { item_name, quantity, unit, min_quantity } = req.body;

        if (!item_name || !unit) {
            return res.status(400).json({ message: 'กรุณากรอกชื่อรายการและหน่วย' });
        }

        const [result] = await pool.execute(
            'INSERT INTO inventory (item_name, quantity, unit, min_quantity) VALUES (?, ?, ?, ?)',
            [item_name, Number(quantity || 0), unit, Number(min_quantity || 0)]
        );

        await logAction(req.user.full_name, `เพิ่มสินค้าใหม่: ${item_name}`);

        const newItem = await queryOne('SELECT * FROM inventory WHERE id = ?', [result.insertId]);
        res.status(201).json(newItem);
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'ไม่สามารถเพิ่มสินค้าได้' });
    }
});

app.put('/api/inventory/:id', requireAuth, async (req, res) => {
    try {
        const { id } = req.params;
        const { item_name, quantity, unit, min_quantity } = req.body;

        if (!item_name || !unit) {
            return res.status(400).json({ message: 'กรุณากรอกชื่อรายการและหน่วย' });
        }

        await pool.execute(
            `UPDATE inventory
             SET item_name = ?, quantity = ?, unit = ?, min_quantity = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?`,
            [item_name, Number(quantity || 0), unit, Number(min_quantity || 0), id]
        );

        await logAction(req.user.full_name, `แก้ไขสินค้า ID ${id}: ${item_name}`);

        const updatedItem = await queryOne('SELECT * FROM inventory WHERE id = ?', [id]);
        res.json(updatedItem);
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'ไม่สามารถแก้ไขสินค้าได้' });
    }
});

app.put('/api/stock/:id', requireAuth, async (req, res) => {
    try {
        const { quantity } = req.body;
        const { id } = req.params;

        await pool.execute(
            'UPDATE inventory SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
            [Number(quantity || 0), id]
        );

        await logAction(req.user.full_name, `อัปเดต stock ID ${id} เป็น ${quantity}`);
        res.json({ message: 'อัปเดต stock เรียบร้อย' });
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'ไม่สามารถอัปเดต stock ได้' });
    }
});

app.delete('/api/inventory/:id', requireAuth, async (req, res) => {
    try {
        const { id } = req.params;
        const item = await queryOne('SELECT * FROM inventory WHERE id = ?', [id]);

        if (!item) {
            return res.status(404).json({ message: 'ไม่พบรายการสินค้า' });
        }

        await pool.execute('DELETE FROM inventory WHERE id = ?', [id]);
        await logAction(req.user.full_name, `ลบสินค้า: ${item.item_name}`);

        res.json({ message: 'ลบข้อมูลสินค้าเรียบร้อย' });
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'ไม่สามารถลบสินค้าได้' });
    }
});

// =========================
// APPOINTMENTS
// =========================
app.get('/api/appointments', requireAuth, async (req, res) => {
    try {
        const q = (req.query.q || '').trim();
        const status = (req.query.status || '').trim();
        const date = (req.query.date || '').trim();

        let sql = 'SELECT * FROM appointments WHERE 1=1';
        const params = [];

        if (q) {
            sql += ' AND (customer_name LIKE ? OR service_name LIKE ? OR COALESCE(phone, "") LIKE ? OR COALESCE(notes, "") LIKE ?)';
            params.push(`%${q}%`, `%${q}%`, `%${q}%`, `%${q}%`);
        }

        if (status) {
            sql += ' AND status = ?';
            params.push(status);
        }

        if (date) {
            sql += ' AND DATE(app_date) = ?';
            params.push(date);
        }

        sql += ' ORDER BY app_date ASC, id DESC';

        const rows = await queryMany(sql, params);
        res.json(rows);
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'ไม่สามารถดึงข้อมูลการนัดหมายได้' });
    }
});

app.post('/api/appointments', requireAuth, async (req, res) => {
    try {
        const { customer_name, phone, service_name, app_date, status, notes } = req.body;

        if (!customer_name || !service_name || !app_date) {
            return res.status(400).json({ message: 'กรุณากรอกชื่อลูกค้า บริการ และวันนัดหมาย' });
        }

        const [result] = await pool.execute(
            'INSERT INTO appointments (customer_name, phone, service_name, app_date, status, notes) VALUES (?, ?, ?, ?, ?, ?)',
            [customer_name, phone || null, service_name, normalizeDateTime(app_date), status || 'pending', notes || null]
        );

        await logAction(req.user.full_name, `เพิ่มนัดหมายใหม่ของ ${customer_name}`);

        const newAppointment = await queryOne('SELECT * FROM appointments WHERE id = ?', [result.insertId]);
        res.status(201).json(newAppointment);
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'ไม่สามารถเพิ่มนัดหมายได้' });
    }
});

app.put('/api/appointments/:id', requireAuth, async (req, res) => {
    try {
        const { id } = req.params;
        const { customer_name, phone, service_name, app_date, status, notes } = req.body;

        if (!customer_name || !service_name || !app_date) {
            return res.status(400).json({ message: 'กรุณากรอกชื่อลูกค้า บริการ และวันนัดหมาย' });
        }

        await pool.execute(
            `UPDATE appointments
             SET customer_name = ?, phone = ?, service_name = ?, app_date = ?, status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?`,
            [customer_name, phone || null, service_name, normalizeDateTime(app_date), status || 'pending', notes || null, id]
        );

        await logAction(req.user.full_name, `แก้ไขนัดหมาย ID ${id} ของ ${customer_name}`);

        const updatedAppointment = await queryOne('SELECT * FROM appointments WHERE id = ?', [id]);
        res.json(updatedAppointment);
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'ไม่สามารถแก้ไขนัดหมายได้' });
    }
});

app.delete('/api/appointments/:id', requireAuth, async (req, res) => {
    try {
        const { id } = req.params;
        const appointment = await queryOne('SELECT * FROM appointments WHERE id = ?', [id]);

        if (!appointment) {
            return res.status(404).json({ message: 'ไม่พบนัดหมาย' });
        }

        await pool.execute('DELETE FROM appointments WHERE id = ?', [id]);
        await logAction(req.user.full_name, `ลบนัดหมายของ ${appointment.customer_name}`);

        res.json({ message: 'ลบนัดหมายเรียบร้อย' });
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'ไม่สามารถลบนัดหมายได้' });
    }
});

// =========================
// LOGS
// =========================
app.get('/api/staff-logs', requireAuth, async (req, res) => {
    try {
        const rows = await queryMany(
            'SELECT * FROM staff_logs ORDER BY created_at DESC, id DESC LIMIT 30'
        );
        res.json(rows);
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'ไม่สามารถดึงข้อมูล log ได้' });
    }
});

async function startServer() {
    try {
        const connection = await pool.getConnection();
        console.log('✅ Connected to MySQL Database');
        connection.release();

        app.listen(PORT, '0.0.0.0', () => {
            console.log(`✅ Server running on port ${PORT}`);
        });
    } catch (error) {
        console.error('❌ Database Connection Error:', error.message);
        process.exit(1);
    }
}

startServer();