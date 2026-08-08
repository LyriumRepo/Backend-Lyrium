require("dotenv").config();
const mysql = require("mysql2");

const db = mysql.createPool({
    host:               process.env.DB_HOST || "127.0.0.1",
    port:               parseInt(process.env.DB_PORT || "3306"),
    user:               process.env.DB_USER || "root",
    password:           process.env.DB_PASSWORD || "",
    database:           process.env.DB_DATABASE || "db-lyriumv1",
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
