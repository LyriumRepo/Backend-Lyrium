const mysql = require("mysql2");

// ─── IMPORTANTE ──────────────────────────────────────────────────────────────
// Cambia "nombre_de_tu_bd" por el nombre real de la base de datos del marketplace
// tal como aparece en XAMPP / phpMyAdmin.
// ─────────────────────────────────────────────────────────────────────────────
const db = mysql.createPool({
    host:               "localhost",
    user:               "root",
    password:           "",             // Contraseña de MySQL en XAMPP (vacía por defecto)
    database:           "db-lyriumv1", // <-- REEMPLAZAR con el nombre real
    waitForConnections: true,
    connectionLimit:    10,
    queueLimit:         0
});

// Verificar conexión al arrancar
db.getConnection((err, connection) => {
    if (err) {
        console.error("❌ Error al conectar a la BD del marketplace:", err.message);
    } else {
        console.log("✅ Conectado a la BD del marketplace (pool)");
        connection.release();
    }
});

// Promisified query helper — permite usar await en server.js
const query = (sql, params = []) =>
    new Promise((resolve, reject) => {
        db.query(sql, params, (err, results) => {
            if (err) reject(err);
            else resolve(results);
        });
    });

module.exports = { db, query };