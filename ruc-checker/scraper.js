const puppeteer = require("puppeteer-extra");
const StealthPlugin = require("puppeteer-extra-plugin-stealth");

puppeteer.use(StealthPlugin());

const SUNAT_TIMEOUT = 30000;
const PAGE_TIMEOUT = 60000;

async function consultarRUC(ruc) {
    const browser = await puppeteer.launch({
        headless: "new",
        slowMo: 50,
        executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || undefined,
        args: ["--no-sandbox", "--disable-setuid-sandbox", "--disable-blink-features=AutomationControlled"]
    });

    const page = await browser.newPage();

    try {
        await page.setUserAgent(
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 " +
            "(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        );

        await page.goto("https://e-consultaruc.sunat.gob.pe/", {
            waitUntil: "domcontentloaded",
            timeout: SUNAT_TIMEOUT
        });

        await page.waitForSelector("#txtRuc", { timeout: SUNAT_TIMEOUT });
        await page.click("#txtRuc", { clickCount: 3 });
        await page.keyboard.press("Backspace");
        await page.type("#txtRuc", ruc, { delay: 120 });
        await page.click("#btnAceptar");

        await page.waitForFunction(
            () => {
                const t = document.body.innerText;
                return t.includes("Raz") || t.includes("RUC") || t.includes("Error") || t.includes("no existe") || t.includes("Resultado");
            },
            { timeout: PAGE_TIMEOUT }
        );
        await new Promise(r => setTimeout(r, 2000));

        const datosPrincipales = await page.evaluate(() => {
            const texto = document.body.innerText;

            let razonSocial = "";
            const rsMatch =
                texto.match(/Nombre\s+o\s+Raz[oó]n\s+Social:\s*(.+)/i) ||
                texto.match(/Raz[oó]n\s+Social:\s*(.+)/i);
            if (rsMatch) razonSocial = rsMatch[1].trim();
            if (!razonSocial) {
                const rucMatch = texto.match(/Número de RUC:\s*(.*)/);
                if (rucMatch) razonSocial = rucMatch[1].trim();
            }

            let nombreComercial = "";
            const ncMatch = texto.match(/Nombre\s+Comercial:\s*(.+)/i);
            if (ncMatch) nombreComercial = ncMatch[1].trim();

            let fechaInicio = "";
            const fechaMatch = texto.match(/Fecha\s+de\s+Inicio\s+de\s+Actividades:\s*(.+)/i);
            if (fechaMatch) fechaInicio = fechaMatch[1].trim();

            let estado = "";
            const estadoMatch = texto.match(/Estado\s+del\s+Contribuyente:\s*(.+)/i);
            if (estadoMatch) estado = estadoMatch[1].trim();

            let condicion = "";
            const condicionMatch = texto.match(/Condici[oó]n\s+del\s+Contribuyente:\s*(.+)/i);
            if (condicionMatch) condicion = condicionMatch[1].trim();

            let actividad = "";
            const actividadMatch = texto.match(
                /Actividad\(es\)\s+Econ[oó]mica\(s\):\s*([\s\S]*?)(?=\s*(?:Sistema\s+de\s+Emisi|Comprobantes|Padrones|Afiliado|Buen\s+Contribuyente|Representante|\n\n|\r\n\r\n))/i
            );
            if (actividadMatch) {
                actividad = actividadMatch[1]
                    .replace(/\r/g, "")
                    .replace(/\n+/g, " ")
                    .replace(/\s{2,}/g, " ")
                    .trim();
            }

            let comprobantes = [];
            const comprobantesMatch = texto.match(
                /Comprobantes\s+Electr[oó]nicos:\s*([\s\S]*?)(?=\s*(?:Padrones|Afiliado|Buen\s+Contribuyente|Representante|Sistema\s+Nacional|\n\n|\r\n\r\n|$))/i
            );
            if (comprobantesMatch) {
                comprobantes = comprobantesMatch[1]
                    .split(/\r?\n/)
                    .map(s => s.trim())
                    .filter(s => s.length > 3);
            }

            return { razonSocial, nombreComercial, fechaInicio, estado, condicion, actividad, comprobantes };
        });

        await page.evaluate(() => {
            const btns = Array.from(document.querySelectorAll("a, button"));
            const btn = btns.find(el =>
                el.innerText && el.innerText.toLowerCase().includes("representante")
            );
            if (btn) btn.click();
        });

        await Promise.race([
            page.waitForFunction(
                () => /\b\d{8}\b/.test(document.body.innerText),
                { timeout: 10000 }
            ).catch(() => null),
            new Promise(r => setTimeout(r, 6000))
        ]);

        await new Promise(r => setTimeout(r, 1500));

        const representantes = await page.evaluate(() => {
            const TIPOS_DOC = ["DNI", "CE", "RUC", "PASAP", "PASAPORTE", "CARNET", "C.E.", "C.I.", "CDI"];
            const reps = [];
            const allRows = Array.from(document.querySelectorAll("tr"));

            for (const row of allRows) {
                const cells = Array.from(row.querySelectorAll("td"));
                if (cells.length < 3) continue;
                const values = cells.map(c => (c.innerText || "").trim());
                const primeraCelda = values[0].toUpperCase();
                const esTipoDoc = TIPOS_DOC.some(t => primeraCelda.includes(t));
                if (!esTipoDoc) continue;
                if (primeraCelda.includes("TIPO")) continue;

                reps.push({
                    tipoDocumento: values[0] || "",
                    nroDocumento:  values[1] || "",
                    nombre:        values[2] || "",
                    cargo:         values[3] || "",
                });
            }

            return reps;
        });

        await browser.close();

        return {
            existe:       true,
            ...datosPrincipales,
            representantes
        };

    } catch (error) {
        await browser.close();
        console.error("Error scraping RUC:", error.message);
        return { error: true, existe: false };
    }
}

async function consultarURL(url) {
    const browser = await puppeteer.launch({
        headless: "new",
        slowMo: 20,
        executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || undefined,
        args: ["--no-sandbox", "--disable-setuid-sandbox", "--disable-blink-features=AutomationControlled"]
    });

    const page = await browser.newPage();

    try {
        await page.setUserAgent(
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 " +
            "(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        );

        await page.goto(url, {
            waitUntil: "domcontentloaded",
            timeout: 30000
        });

        await new Promise(resolve => setTimeout(resolve, 2000));

        const data = await page.evaluate(() => {
            const clean = (txt) => (txt || "").replace(/\s+/g, " ").trim();

            const metaDescription =
                document.querySelector('meta[name="description"]')?.content ||
                document.querySelector('meta[property="og:description"]')?.content ||
                "";

            const h1 = [...document.querySelectorAll("h1")].map(e => clean(e.innerText)).filter(Boolean).join(" | ");
            const h2 = [...document.querySelectorAll("h2")].map(e => clean(e.innerText)).filter(Boolean).join(" | ");
            const h3 = [...document.querySelectorAll("h3")].map(e => clean(e.innerText)).filter(Boolean).join(" | ");

            return {
                titulo:          clean(document.title),
                metaDescription: clean(metaDescription),
                encabezados:     clean([h1, h2, h3].filter(Boolean).join(" | ")),
                texto:           clean(document.body?.innerText || ""),
                enlaces:         document.querySelectorAll("a").length
            };
        });

        await browser.close();
        return { existe: true, ...data };

    } catch (error) {
        await browser.close();
        return { error: true, mensaje: error.message };
    }
}

module.exports = { consultarRUC, consultarURL };
