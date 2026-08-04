process.env.PUPPETEER_EXECUTABLE_PATH = String.raw`C:\Program Files\Google\Chrome\Application\chrome.exe`;
const { consultarRUC } = require("./scraper");

(async () => {
    console.log("Testing SUNAT scraping for RUC 20512345678...");
    try {
        const result = await Promise.race([
            consultarRUC("20512345678"),
            new Promise((_, reject) => setTimeout(() => reject(new Error("Timeout 50s")), 50000))
        ]);
        console.log("Result:", JSON.stringify(result, null, 2));
    } catch (e) {
        console.error("ERROR:", e.message);
    }
    process.exit(0);
})();
