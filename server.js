const express = require('express');
const mysql = require('mysql2');
const cors = require('cors');
const path = require('path'); 
require('dotenv').config();

const app = express();
app.use(cors());
app.use(express.json());


app.use(express.static(path.join(__dirname, './')));

const db = mysql.createConnection({
    host: process.env.DB_HOST,
    port: process.env.DB_PORT, 
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


app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});
// ----------------------------------------------

app.get('/api/appointments', (req, res) => {
    db.query('SELECT * FROM appointments', (err, result) => {
        if (err) return res.status(500).send(err);
        res.json(result);
    });
});

app.get('/api/customers', (req, res) => {
    db.query('SELECT * FROM customers', (err, result) => {
        if (err) return res.status(500).send(err);
        res.json(result);
    });
});

app.put('/api/stock/:id', (req, res) => {
    const { quantity } = req.body;
    const { id } = req.params;
    
    db.query('UPDATE inventory SET quantity = ? WHERE id = ?', [quantity, id], (err) => {
        if (err) return res.status(500).send(err);

        const logMsg = `Updated stock ID: ${id} to ${quantity}`;
        db.query('INSERT INTO staff_logs (staff_name, action_text) VALUES (?, ?)', ['Admin', logMsg]);
        
        res.send("Stock Updated and Logged!");
    });
});


const PORT = process.env.PORT || 3000;
app.listen(PORT, '0.0.0.0', () => {
    console.log(`✅ Server is running on port ${PORT}`);
});