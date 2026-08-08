// apisperuClient.js — Lyrium Biomarketplace RPA
//
// Plan B de verificación de RUC: cuando el scraping directo a SUNAT
// (scraper.js) falla por un bloqueo del WAF, timeout o caída del sitio,
// se consulta esta API de terceros en vez de rendirse. NO es un segundo
// RPA — es un simple cliente HTTP a un servicio ya existente.
//
// Limitación conocida del plan gratuito de apisperu.com: no devuelve
// `actEconomicas` ni `cpPago` (comprobantes electrónicos) — siempre vienen
// vacíos sin importar la empresa. Por eso el resultado se marca con
// `fuenteIncompleta: true`, que engine.js usa para no aplicar el filtro
// de "emite comprobantes" con datos que esta fuente no puede confirmar.

const axios = require("axios");

const APISPERU_TOKEN = process.env.APISPERU_TOKEN;
const APISPERU_TIMEOUT = 10000;

async function consultarRUCFallback(ruc) {
    if (!APISPERU_TOKEN) {
        console.error("⚠ APISPERU_TOKEN no configurado — fallback de RUC deshabilitado");
        return { error: true, existe: false };
    }
    try {
        const { data } = await axios.get(`https://dniruc.apisperu.com/api/v1/ruc/${ruc}`, {
            params: { token: APISPERU_TOKEN },
            timeout: APISPERU_TIMEOUT
        });

        if (!data || !data.ruc) {
            return { error: true, existe: false };
        }

        return {
            existe: true,
            razonSocial: data.razonSocial || null,
            nombreComercial: data.nombreComercial || null,
            fechaInicio: data.fechaInscripcion || null,
            estado: data.estado || null,
            condicion: data.condicion || null,
            actividad: Array.isArray(data.actEconomicas) && data.actEconomicas.length
                ? data.actEconomicas.join(", ")
                : null,
            comprobantes: Array.isArray(data.cpPago) ? data.cpPago : [],
            representantes: [], // este endpoint no expone representantes legales
            fuenteIncompleta: true,
            fuente: "apisperu.com"
        };
    } catch (err) {
        return { error: true, existe: false };
    }
}

module.exports = { consultarRUCFallback };
