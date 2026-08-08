require("dotenv").config();
const express = require("express");
const cors = require("cors");
const bcrypt = require("bcrypt");
const multer = require("multer");
const axios = require("axios");
const nodemailer = require("nodemailer");
const crypto = require("crypto");
const { consultarRUC, consultarURL } = require("./scraper");
const { consultarRUCFallback } = require("./apisperuClient");
const { extraerTextoPDF } = require("./pdfExtractor");
const { evaluarEmpresa } = require("./engine");
const { query } = require("./db");

const app = express();
app.use(cors());
app.use(express.json());

// ─── Config ──────────────────────────────────────────────────────────────────
const LARAVEL_URL = "http://localhost:8000";         // URL del backend Laravel
const INTERNAL_SECRET = "rpa_secret_lyrium_2024";       // Debe coincidir con .env INTERNAL_RPA_SECRET
const TIPOS_PDF = ["catalogo", "ficha", "boleta", "factura"];

// ─── Multer (PDF en memoria, máx 10 MB) ──────────────────────────────────────
const upload = multer({
    storage: multer.memoryStorage(),
    limits: { fileSize: 10 * 1024 * 1024 },
    fileFilter: (req, file, cb) => {
        if (file.mimetype === "application/pdf") {
            cb(null, true);
        } else {
            cb(new Error("Solo se permiten archivos PDF"));
        }
    }
});

// ─── Mailer ────────────────────────────────────────────────────────────────────
// Usa las mismas credenciales SMTP que el .env de Laravel.
// Si MAIL_MAILER=log en Laravel (modo desarrollo), esto NO enviará correos
// reales: la promesa fallará y se capturará en el catch del endpoint.
const mailTransporter = nodemailer.createTransport({
    host: process.env.MAIL_HOST || "127.0.0.1",
    port: parseInt(process.env.MAIL_PORT || "2525"),
    secure: false,
    auth: (process.env.MAIL_USERNAME && process.env.MAIL_USERNAME !== "null")
        ? { user: process.env.MAIL_USERNAME, pass: process.env.MAIL_PASSWORD }
        : undefined,
    tls: { rejectUnauthorized: false }
});

// ─── Validaciones básicas ─────────────────────────────────────────────────────
function esRUCValido(ruc) { return /^\d{11}$/.test(String(ruc || "").trim()); }
function esDNIValido(dni) { return /^\d{8}$/.test(String(dni || "").trim()); }
function esEmailValido(email) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || "").trim()); }
function esUrlValida(valor) {
    try {
        const u = new URL(valor);
        return u.protocol === "http:" || u.protocol === "https:";
    } catch { return false; }
}

// ─── Slug helper (igual que Laravel Str::slug) ────────────────────────────────
function slugify(text) {
    return (text || "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");
}

async function slugUnico(base) {
    let slug = slugify(base);
    let candidato = slug;
    let i = 1;
    while (true) {
        const rows = await query("SELECT id FROM stores WHERE slug = ? LIMIT 1", [candidato]);
        if (rows.length === 0) return candidato;
        candidato = `${slug}-${i++}`;
    }
}

// ─── Username único (igual que Laravel) ──────────────────────────────────────
async function usernameUnico(base) {
    let username = slugify(base).replace(/-/g, "_");
    let candidato = username;
    let i = 1;
    while (true) {
        const rows = await query("SELECT id FROM users WHERE username = ? LIMIT 1", [candidato]);
        if (rows.length === 0) return candidato;
        candidato = `${username}_${i++}`;
    }
}

// ─── Obtener role_id de 'seller' en Spatie ────────────────────────────────────
async function getSellerRoleId() {
    const rows = await query(
        "SELECT id FROM roles WHERE name = 'seller' AND guard_name = 'web' LIMIT 1"
    );
    if (rows.length === 0) throw new Error("Rol 'seller' no encontrado en la BD");
    return rows[0].id;
}

// ─── Crear usuario del marketplace si aún no existe (compartido) ─────────────
async function crearUsuarioSiNoExiste({ user_id, nombre, correo, telefono, ruc, password }) {
    if (user_id) return user_id;

    const passwordHash = await bcrypt.hash(password, 10);
    const username = await usernameUnico(nombre);
    const now = new Date().toISOString().slice(0, 19).replace("T", " ");

    const userResult = await query(
        `INSERT INTO users
         (name, username, email, nicename, phone, document_type, document_number, password, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, 'RUC', ?, ?, ?, ?)`,
        [nombre, username, correo, slugify(nombre), telefono, ruc, passwordHash, now, now]
    );
    const newUserId = userResult.insertId;

    try {
        const roleId = await getSellerRoleId();
        await query(
            "INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id) VALUES (?, 'App\\\\Models\\\\User', ?)",
            [roleId, newUserId]
        );
    } catch (roleErr) {
        console.error("⚠ No se pudo asignar rol seller:", roleErr.message);
    }

    return newUserId;
}

// ─── Avisar a los administradores (tabla notifications, formato Laravel) ─────
// Escribe directo en la tabla `notifications` (mismo patrón que ya usa este
// servicio para users/model_has_roles) para que el bell/toast del panel admin
// la recoja sin cambios adicionales en el backend Laravel.
async function notificarAdmins(tipo, data) {
    try {
        const admins = await query(
            `SELECT u.id FROM users u
             INNER JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'App\\\\Models\\\\User'
             INNER JOIN roles r ON r.id = mhr.role_id
             WHERE r.name = 'administrator'`
        );
        if (admins.length === 0) return;

        const now = new Date().toISOString().slice(0, 19).replace("T", " ");
        for (const admin of admins) {
            await query(
                `INSERT INTO notifications (id, type, notifiable_type, notifiable_id, data, created_at, updated_at)
                 VALUES (?, ?, 'App\\\\Models\\\\User', ?, ?, ?, ?)`,
                [crypto.randomUUID(), tipo, admin.id, JSON.stringify(data), now, now]
            );
        }
    } catch (notifErr) {
        console.error("⚠ No se pudo notificar a los administradores:", notifErr.message);
    }
}


// =============================================================================
// POST /registro-seller
// Formulario principal de registro de vendedor en el marketplace.
// Acepta multipart/form-data para PDFs o application/json para texto/url.
// =============================================================================
app.post("/registro-seller", upload.single("archivoPDF"), async (req, res) => {

    // ── 1. Normalizar campos del body ────────────────────────────────────────
    let {
        nombre, dni, ruc, telefono, correo, password,
        categoria, descripcionActividad,
        tipoEvidencia, valorEvidencia, textoEvidencia,
        user_id   // opcional: si el usuario ya fue creado por Laravel auth
    } = req.body;

    nombre = String(nombre || "").trim();
    dni = String(dni || "").trim();
    ruc = String(ruc || "").trim();
    telefono = String(telefono || "").trim();
    correo = String(correo || "").trim();
    password = String(password || "").trim();
    descripcionActividad = String(descripcionActividad || "").trim();
    tipoEvidencia = String(tipoEvidencia || "texto").trim().toLowerCase();
    valorEvidencia = String(valorEvidencia || "").trim();
    textoEvidencia = String(textoEvidencia || "").trim();
    user_id = user_id ? parseInt(user_id) : null;

    // ── 2. Validaciones de formato ───────────────────────────────────────────
    if (!nombre || !dni || !ruc || !telefono || !correo) {
        return res.status(400).json({ error: "Datos incompletos" });
    }
    if (!esRUCValido(ruc)) return res.status(400).json({ error: "RUC inválido (11 dígitos numéricos)" });
    if (!esDNIValido(dni)) return res.status(400).json({ error: "DNI inválido (8 dígitos numéricos)" });
    if (!esEmailValido(correo)) return res.status(400).json({ error: "Correo inválido" });

    // Si no viene user_id (el usuario se crea aquí), la contraseña es obligatoria
    if (!user_id && !password) {
        return res.status(400).json({ error: "La contraseña es requerida" });
    }

    try {
        // ── 3. Verificar duplicados en el marketplace ────────────────────────
        if (!user_id) {
            const emailExiste = await query(
                "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1", [correo]
            );
            if (emailExiste.length > 0) {
                return res.status(409).json({ error: "Este correo ya está registrado en el marketplace" });
            }
        }

        const rucExiste = await query(
            "SELECT id FROM stores WHERE ruc = ? AND deleted_at IS NULL LIMIT 1", [ruc]
        );
        if (rucExiste.length > 0) {
            return res.status(409).json({ error: "Este RUC ya tiene una tienda registrada en el marketplace" });
        }

        // ── 4. Consultar SUNAT ───────────────────────────────────────────────
        let dataSunat;
        let motivoFalloRpa = null;
        try {
            dataSunat = await Promise.race([
                consultarRUC(ruc),
                new Promise((_, reject) =>
                    setTimeout(() => reject(new Error("Timeout consultando SUNAT (45s)")), 45000)
                )
            ]);
            if (dataSunat.error) {
                motivoFalloRpa = "SUNAT rechazó o no respondió la consulta automática (posible bloqueo del sitio)";
            }
        } catch (sunatErr) {
            dataSunat = { error: true, existe: false };
            motivoFalloRpa = sunatErr.message;
        }

        // ── Plan B: SUNAT bloqueó/no respondió → intentar apisperu.com antes
        // de rendirse. No es un segundo RPA, solo consume esa API externa.
        if (motivoFalloRpa) {
            const fallback = await consultarRUCFallback(ruc);
            if (!fallback.error && fallback.existe) {
                dataSunat = fallback;
                motivoFalloRpa = null; // se recuperó por la fuente secundaria
            }
        }

        // Ni SUNAT ni el respaldo respondieron — el scraper devuelve el mismo
        // { existe:false } tanto para esto como para un RUC genuinamente
        // inexistente, así que se distingue aquí. No se rechaza la solicitud:
        // se guarda para revisión manual y se avisa al admin, en vez de
        // perderla en silencio como pasaba antes (detectado 2026-08-08).
        if (motivoFalloRpa) {
            user_id = await crearUsuarioSiNoExiste({ user_id, nombre, correo, telefono, ruc, password });
            const now = new Date().toISOString().slice(0, 19).replace("T", " ");
            const diagnosticoFallo = [
                "⚠ No se pudo verificar automáticamente el RUC con SUNAT en este momento.",
                `Motivo técnico: ${motivoFalloRpa}`,
                "La solicitud fue registrada y requiere que un administrador verifique el RUC manualmente en SUNAT antes de aprobar o rechazar.",
            ];

            const appResult = await query(
                `INSERT INTO seller_applications
                 (user_id, nombre_comercial, ruc, dni, telefono, correo, categoria,
                  razon_social, sunat_data, tipo_evidencia, evidencia_valor,
                  etapa, score, riesgo, estado, diagnostico, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                [
                    user_id, nombre, ruc, dni, telefono, correo, categoria || null,
                    null,
                    JSON.stringify({ rpa_fallo: true, motivo: motivoFalloRpa }),
                    tipoEvidencia, valorEvidencia || null,
                    1, 0, "MEDIO", "REVISION",
                    JSON.stringify(diagnosticoFallo),
                    now, now
                ]
            );
            const applicationId = appResult.insertId;

            await notificarAdmins("rpa_verification_failed", {
                subject: `No se pudo verificar el RUC ${ruc} con SUNAT — requiere revisión manual`,
                seller_name: nombre,
                ruc,
                application_id: applicationId,
                reason: motivoFalloRpa,
                action_url: "/admin/sellers/solicitudes",
            });

            return res.json({
                estado: "REVISION",
                score: 0,
                riesgo: "MEDIO",
                etapa: 1,
                diagnostico: diagnosticoFallo,
                application_id: applicationId,
                store_id: null,
            });
        }

        if (!dataSunat.existe) {
            return res.json({ estado: "RECHAZADO", diagnostico: ["RUC no existe en SUNAT"] });
        }

        // ── Validaciones críticas de Etapa 1 (booleanas) ─────────────────────────
        const tieneComprobantes = Array.isArray(dataSunat.comprobantes)
            ? dataSunat.comprobantes.length > 0
            : Boolean(dataSunat.comprobantes);

        const sunatData = {
            // Datos extraídos
            razonSocial: dataSunat.razonSocial || null,
            nombreComercial: dataSunat.nombreComercial || null,
            fechaInicio: dataSunat.fechaInicio || null,
            actividad: dataSunat.actividad || null,
            estado: dataSunat.estado || null,
            condicion: dataSunat.condicion || null,
            comprobantes: dataSunat.comprobantes || [],
            representantes: dataSunat.representantes || [],
            fuente: dataSunat.fuente || "sunat_rpa",

            // Las 4 validaciones críticas — booleanas
            validacion: {
                rucExiste: Boolean(dataSunat.existe),
                estadoActivo: dataSunat.estado === "ACTIVO",
                condicionHabido: dataSunat.condicion === "HABIDO",
                emiteComprobante: dataSunat.fuenteIncompleta ? null : tieneComprobantes,
            }
        };

        // ── 5. Procesar evidencia ────────────────────────────────────────────
        let evidenciaFinal = { tipo: tipoEvidencia, valor: valorEvidencia, texto: textoEvidencia };

        if (tipoEvidencia === "url") {
            if (!esUrlValida(valorEvidencia)) {
                return res.status(400).json({ error: "URL de evidencia inválida" });
            }
            const urlInfo = await consultarURL(valorEvidencia);
            if (urlInfo.error) {
                return res.status(400).json({ error: `No se pudo consultar la URL: ${urlInfo.mensaje}` });
            }
            evidenciaFinal = {
                tipo: "url",
                valor: valorEvidencia,
                texto: [urlInfo.titulo, urlInfo.metaDescription, urlInfo.encabezados, urlInfo.texto]
                    .filter(Boolean).join(" ")
            };
        }

        if (TIPOS_PDF.includes(tipoEvidencia)) {
            if (!req.file) {
                return res.status(400).json({ error: "Debes adjuntar un archivo PDF" });
            }
            const { texto, metodo } = await extraerTextoPDF(req.file.buffer);
            if (!texto || texto.length < 30) {
                return res.status(400).json({ error: "No se pudo extraer contenido del PDF" });
            }
            evidenciaFinal = {
                tipo: tipoEvidencia,
                valor: req.file.originalname,
                texto,
                metodo
            };
        }

        // ── 6. Evaluación RPA ────────────────────────────────────────────────
        const resultado = evaluarEmpresa({
            nombre,
            dni,
            ruc,
            categoria,
            descripcionActividad,
            evidencia: evidenciaFinal
        }, dataSunat);

        // Transparencia para el admin: si se usó la fuente de respaldo, que
        // quede visible en el mismo panel de diagnóstico que ya usa el RPA
        // normal (sin necesidad de tocar el frontend).
        if (dataSunat.fuenteIncompleta) {
            resultado.diagnostico = [
                "ℹ️ RUC verificado vía fuente secundaria (apisperu.com) — SUNAT no respondió directamente. No se pudo confirmar comprobantes electrónicos automáticamente; verifícalo manualmente si es relevante.",
                ...resultado.diagnostico
            ];
        }

        const now = new Date().toISOString().slice(0, 19).replace("T", " ");

        // ── 7. Crear usuario en el marketplace (si no viene user_id) ─────────
        // NOTA: El OTP de verificación de correo ya no se dispara aquí.
        // Ahora se envía solo si el usuario lo solicita desde la
        // pantalla de resultado (POST /enviar-diagnostico), o cuando
        // el admin aprueba la solicitud.
        user_id = await crearUsuarioSiNoExiste({ user_id, nombre, correo, telefono, ruc, password });

        // ── 8. Guardar solicitud en seller_applications ─────────────────────
        const appResult = await query(
            `INSERT INTO seller_applications
             (user_id, nombre_comercial, ruc, dni, telefono, correo, categoria,
              razon_social, sunat_data, tipo_evidencia, evidencia_valor,
              etapa, score, riesgo, estado, diagnostico, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            [
                user_id,
                nombre, ruc, dni, telefono, correo, categoria || null,
                dataSunat.razonSocial || null,
                JSON.stringify(sunatData),
                tipoEvidencia,
                evidenciaFinal.valor || null,
                resultado.etapa || 1,
                resultado.score,
                resultado.riesgo,
                resultado.estado,
                JSON.stringify(resultado.diagnostico),
                now, now
            ]
        );
        const applicationId = appResult.insertId;

        // ── 9. Crear tienda solo si fue ACEPTADO ────────────────────────────
        let storeId = null;

        if (resultado.estado === "ACEPTADO") {
            try {
                const slug = await slugUnico(nombre);
                const repNombre = dataSunat.representantes?.[0]?.nombre || null;

                const storeResult = await query(
                    `INSERT INTO stores
                     (owner_id, ruc, trade_name, razon_social, nombre_comercial,
                      corporate_email, slug, phone, status,
                      rep_legal_nombre, rep_legal_dni,
                      activity, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)`,
                    [
                        user_id, ruc, nombre,
                        dataSunat.razonSocial || null,
                        dataSunat.nombreComercial && dataSunat.nombreComercial !== "-"
                            ? dataSunat.nombreComercial
                            : nombre,
                        correo, slug, telefono,
                        repNombre,
                        dni,
                        dataSunat.actividad || null,
                        now, now
                    ]
                );
                storeId = storeResult.insertId;

                // Vincular store_id en la solicitud
                await query(
                    "UPDATE seller_applications SET store_id = ? WHERE id = ?",
                    [storeId, applicationId]
                );
            } catch (storeErr) {
                console.error("⚠ Error al crear tienda:", storeErr.message);
                // La solicitud ya quedó guardada; el admin puede crear la tienda manualmente
            }
        }

        // ── 10. Respuesta al frontend ────────────────────────────────────────
        return res.json({
            estado: resultado.estado,
            score: resultado.score,
            riesgo: resultado.riesgo,
            etapa: resultado.etapa,
            diagnostico: resultado.diagnostico,
            application_id: applicationId,
            store_id: storeId
        });

    } catch (error) {
        console.error("Error en /registro-seller:", error);
        res.status(500).json({ error: "Error interno del servidor" });
    }
});


// =============================================================================
// POST /enviar-diagnostico
// El usuario, desde la pantalla de resultado, pide que le envíen
// el diagnóstico completo de su solicitud por correo.
// Body: { application_id, correo }
// =============================================================================
app.post("/enviar-diagnostico", async (req, res) => {
    const { application_id, correo } = req.body;

    if (!application_id || !correo) {
        return res.status(400).json({ error: "Faltan datos (application_id, correo)" });
    }

    try {
        const rows = await query(
            "SELECT * FROM seller_applications WHERE id = ? AND correo = ? LIMIT 1",
            [application_id, correo]
        );

        if (rows.length === 0) {
            return res.status(404).json({ error: "Solicitud no encontrada" });
        }

        const app_ = rows[0];
        const diagnostico = JSON.parse(app_.diagnostico || "[]");

        const htmlDiagnostico = diagnostico.length
            ? `<ul>${diagnostico.map(d => `<li>${d}</li>`).join("")}</ul>`
            : "<p>No hay observaciones adicionales.</p>";

        const html = `
            <h2>Resultado de tu solicitud — ${app_.nombre_comercial}</h2>
            <p><strong>Estado:</strong> ${app_.estado}</p>
            <p><strong>Puntaje:</strong> ${app_.score}/100</p>
            <p><strong>Riesgo:</strong> ${app_.riesgo}</p>
            <h3>Diagnóstico detallado:</h3>
            ${htmlDiagnostico}
        `;

        await mailTransporter.sendMail({
            from: process.env.MAIL_FROM_ADDRESS || "no-reply@lyrium.com",
            to: correo,
            subject: `Diagnóstico de tu solicitud — ${app_.estado}`,
            html
        });

        return res.json({ success: true, message: "Diagnóstico enviado a tu correo" });

    } catch (error) {
        console.error("Error en /enviar-diagnostico:", error);
        return res.status(500).json({ error: "No se pudo enviar el diagnóstico. Intenta más tarde." });
    }
});


// =============================================================================
// GET /health  — verificar que el servicio RPA está activo
// =============================================================================
app.get("/health", (req, res) => {
    res.json({ status: "ok", service: "ruc-checker-rpa", timestamp: new Date().toISOString() });
});


app.listen(3001, () => {
    console.log("🤖 Servicio RPA corriendo en http://localhost:3001");
});