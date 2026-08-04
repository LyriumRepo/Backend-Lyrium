const pdfParse = require("pdf-parse");
const Tesseract = require("tesseract.js");
const { createCanvas } = require("canvas");

const TEXTO_MINIMO = 80;
const MAX_PAGINAS  = 5;

async function renderPaginaACanvas(pdfDoc, numPagina) {
    const pagina  = await pdfDoc.getPage(numPagina);
    const escala  = 2.0;
    const viewport = pagina.getViewport({ scale: escala });

    const canvas  = createCanvas(viewport.width, viewport.height);
    const context = canvas.getContext("2d");

    await pagina.render({
        canvasContext: context,
        viewport
    }).promise;

    return canvas.toBuffer("image/png");
}

async function extraerTextoPDF(buffer) {
    // ===== INTENTO 1: pdf-parse (texto embebido) =====
    let nPaginas = 1;
    try {
        const parsed = await pdfParse(buffer);
        const texto  = (parsed.text || "").replace(/\s+/g, " ").trim();
        nPaginas     = parsed.numpages || 1;

        if (texto.length >= TEXTO_MINIMO) {
            return { texto, metodo: "pdf-parse" };
        }
    } catch (_) {}

    // ===== INTENTO 2: pdfjs + canvas + Tesseract OCR =====
    try {
        const pdfjsLib = require("pdfjs-dist/legacy/build/pdf.js");

        const pdfDoc  = await pdfjsLib.getDocument({ data: new Uint8Array(buffer) }).promise;
        const paginas = Math.min(pdfDoc.numPages || nPaginas, MAX_PAGINAS);
        const textos  = [];

        for (let i = 1; i <= paginas; i++) {
            try {
                const imgBuffer = await renderPaginaACanvas(pdfDoc, i);
                const { data }  = await Tesseract.recognize(imgBuffer, "spa+eng", { logger: () => {} });
                textos.push(data.text || "");
            } catch (_) {}
        }

        const texto = textos.join(" ").replace(/\s+/g, " ").trim();
        return { texto, metodo: "ocr" };

    } catch (err) {
        console.error("Error OCR pdfExtractor:", err.message);
        return { texto: "", metodo: "ocr-error" };
    }
}

module.exports = { extraerTextoPDF };