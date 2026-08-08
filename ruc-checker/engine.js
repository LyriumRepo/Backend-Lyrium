// engine.js — Lyrium Biomarketplace RPA

// ===== UTILIDADES =====

function limpiar(txt) {
    return txt?.toLowerCase().replace(/[^a-z0-9]/g, "") || "";
}

function calcularSimilitud(a, b) {
    a = limpiar(a);
    b = limpiar(b);
    if (!a || !b) return 0;
    if (a === b) return 1;
    if (a.includes(b) || b.includes(a)) return 0.5;
    return 0;
}

function calcularAntigüedad(fechaStr) {
    if (!fechaStr) return "desconocida";
    const [d, m, y] = fechaStr.split("/");
    const fecha = new Date(`${y}-${m}-${d}`);
    const hoy = new Date();
    const años = (hoy - fecha) / (1000 * 60 * 60 * 24 * 365);
    if (años >= 2) return "mayor2";
    if (años >= 1) return "entre1y2";
    return "menor1";
}

function esActivo(estado) {
    return estado?.toLowerCase().includes("activo");
}

function esHabido(condicion) {
    return condicion?.toLowerCase().includes("habido");
}

function emiteComprobantes(comprobantes) {
    if (!comprobantes) return false;

    // El scraper actualizado devuelve un array de comprobantes,
    // pero registros antiguos pueden traerlo como string.
    const texto = Array.isArray(comprobantes)
        ? comprobantes.join(" ").toLowerCase()
        : String(comprobantes).toLowerCase();

    if (!texto) return false;

    return texto.includes("factura") || texto.includes("boleta");
}

function validarRepresentante(dni, data) {
    return data.representantes?.some(r => r.dni === dni);
}

// Keywords del rubro salud y bienestar
const KEYWORDS_RUBRO = [
    "farmacia", "farma", "salud", "health", "médico", "medico", "medicina",
    "clínica", "clinica", "hospital", "laboratorio", "dental", "odonto",
    "veterinaria", "vet", "nutrición", "nutricion", "nutri", "bienestar",
    "wellness", "natural", "orgánico", "organico", "herbal", "hierba",
    "terapia", "fisioterapia", "rehabilitación", "rehabilitacion",
    "suplemento", "vitamina", "cosmético", "cosmetico", "higiene",
    "pediatría", "pediatria", "ginecología", "ginecologia",
    "psicología", "psicologia", "agro", "agricultura", "botánico", "botanico",
    "fitoterapia", "homeopatía", "homeopatia", "spa", "estética", "estetica",
    "terapeutico", "terapéutico", "quiropráctico", "quiropractico",
    "dermatología", "dermatologia", "oftalmología", "oftalmologia",
    "oncología", "oncologia", "cardiología", "cardiologia"
];

function evaluarContraRubro(texto) {
    if (!texto) return "ninguno";
    const t = texto.toLowerCase();
    const coincidencias = KEYWORDS_RUBRO.filter(k => t.includes(k)).length;
    if (coincidencias >= 2) return "alto";
    if (coincidencias === 1) return "medio";
    return "ninguno";
}


// ===== ETAPA 1: EVALUACIÓN BOOLEANA =====
// Si falla cualquier condición → rechazo inmediato, no pasa a etapa 2

function evaluacionBooleana(data) {
    const rechazos = [];

    if (!data.existe) {
        rechazos.push("RUC no existe en SUNAT");
    }

    if (!esActivo(data.estado)) {
        rechazos.push("La empresa no está activa en SUNAT");
    }

    if (!esHabido(data.condicion)) {
        rechazos.push("La empresa figura como no habida en SUNAT");
    }

    // Cuando el dato viene de la fuente de respaldo (apisperu.com, plan
    // gratuito) no se expone el detalle de comprobantes electrónicos —
    // no se puede aplicar este filtro con certeza, así que se omite en
    // vez de rechazar por falta de datos que esa fuente nunca entrega.
    if (!data.fuenteIncompleta && !emiteComprobantes(data.comprobantes)) {
        rechazos.push("La empresa no emite comprobantes de pago (factura o boleta)");
    }

    return {
        aprobada: rechazos.length === 0,
        rechazos
    };
}


// ===== ETAPA 2: EVALUACIÓN POR PUNTAJE =====
// Puntaje máximo: 100
// ≥70      → Aprobación automática
// 45–69    → Revisión manual
// ≤44      → Rechazo automático

function evaluacionPuntaje(input, data) {
    let score = 0;
    let diagnostico = [];
    let rechazoInmediato = false;

    const nombreSunat = data.nombreComercial && data.nombreComercial !== "-"
        ? data.nombreComercial
        : data.razonSocial;

    // ===== 1. RAZÓN SOCIAL VS RUBRO (máx +10) =====
    const nivelRazon = evaluarContraRubro(data.razonSocial);

    if (nivelRazon === "alto") {
        score += 10;
        diagnostico.push("Razón social claramente relacionada con bienestar/salud (+10)");
    } else if (nivelRazon === "medio") {
        score += 5;
        diagnostico.push("Razón social parcialmente relacionada con bienestar/salud (+5)");
    } else {
        diagnostico.push("Razón social sin relación directa con el rubro (0)");
    }

    // ===== 2. NOMBRE COMERCIAL (máx +10) =====
    // Triangulación de 3 fuentes: SUNAT · nombre declarado en el formulario · evidencia
    // Cubre el caso de marcas distintas a la razón social (ej: TIENS vs TIANSHI PERU S.A.C.)
    const sim = calcularSimilitud(input.nombre, nombreSunat);
    const coincidenConSunat = sim >= 0.5;   // coincidencia exacta o parcial con SUNAT

    // Verificar si el nombre declarado por el usuario aparece en el texto de su evidencia.
    // Se usa normalizarTexto que es una function declaration y está disponible por hoisting.
    const textoEvidenciaNombre = normalizarTexto([
        input?.evidencia?.texto,
        input?.evidencia?.contenido,
        input?.evidencia?.ocr,
        input?.evidencia?.descripcion,
        input?.evidencia?.valor
    ].filter(Boolean).join(" "));
    const nombreNorm = normalizarTexto(input?.nombre || "");
    const apareceEnEvidencia = nombreNorm.length >= 4 && textoEvidenciaNombre.includes(nombreNorm);

    if (coincidenConSunat && apareceEnEvidencia) {
        score += 10;
        diagnostico.push("Nombre comercial coincide con SUNAT y confirmado en la evidencia (+10)");
    } else if (!coincidenConSunat && apareceEnEvidencia) {
        score += 7;
        diagnostico.push(`Nombre "${input.nombre}" no coincide con SUNAT pero confirmado en la evidencia (+7)`);
    } else if (coincidenConSunat) {
        score += 5;
        diagnostico.push("Nombre comercial coincide con SUNAT pero no aparece en la evidencia (+5)");
    } else {
        diagnostico.push("Nombre comercial no coincide con SUNAT ni aparece en la evidencia (0)");
    }

    // ===== 3. ACTIVIDAD(ES) ECONÓMICA (máx +10) =====
    const nivelActividad = evaluarContraRubro(data.actividad);

    if (nivelActividad === "alto") {
        score += 10;
        diagnostico.push("Actividad económica claramente relacionada con bienestar/salud (+10)");
    } else if (nivelActividad === "medio") {
        score += 5;
        diagnostico.push("Actividad económica parcialmente relacionada con bienestar/salud (+5)");
    } else {
        diagnostico.push("Actividad económica sin relación directa con el rubro (0)");
    }

    // ===== 4. DNI REPRESENTANTE LEGAL (máx +10) =====
    const dniOk = validarRepresentante(input.dni, data);

    if (dniOk) {
        score += 10;
        diagnostico.push("DNI corresponde al representante legal del RUC (+10)");
    } else {
        diagnostico.push("DNI no corresponde a ningún representante legal (0)");
    }

    // ===== 5. ANTIGÜEDAD DE LA EMPRESA (máx +10) =====
    const antiguedad = calcularAntigüedad(data.fechaInicio);

    if (antiguedad === "mayor2") {
        score += 10;
        diagnostico.push("Empresa con más de 2 años de actividad (+10)");
    } else if (antiguedad === "entre1y2") {
        score += 5;
        diagnostico.push("Empresa con 1 a 2 años de actividad (+5)");
    } else {
        diagnostico.push("Empresa con menos de 1 año de actividad (0)");
    }

    // ===== 6. EVIDENCIA DE ACTIVIDAD (máx +50) =====
    // TODO: Implementar análisis de archivo (boleta, catálogo, ficha técnica) y/o URL
    // Puntajes: coherencia alta +50 | coherencia parcial +25 | sin coherencia → rechazoInmediato = true
    // ===== 6. EVIDENCIA DE ACTIVIDAD (máx +50) =====

    const KEYWORDS_COMERCIO = [
        "catalogo", "catálogo", "producto", "productos", "servicio", "servicios",
        "precio", "precios", "venta", "ventas", "comprar", "cotizacion", "cotización",
        "whatsapp", "telefono", "teléfono", "contacto", "direccion", "dirección",
        "web", "website", "facebook", "instagram", "tiktok", "linkedin",
        "marca", "modelo", "ficha tecnica", "ficha técnica", "especificaciones",
        "garantia", "garantía", "envio", "envío", "delivery", "stock"
    ];

    // Keywords específicas por categoría del marketplace
    // Capa 2: si la evidencia coincide con la categoría declarada → bonus +5
    const KEYWORDS_CATEGORIA = {
        "alimentos_saludables": [
            "alimento", "organico", "vegano", "vegana", "integral", "cereal",
            "quinua", "kiwicha", "maca", "keto", "snack", "granola", "fruta",
            "verdura", "agroecologico", "sin gluten", "proteina", "dieta",
            "saludable", "macrobiotico", "fermentado", "probiotico", "kombucha"
        ],
        "atencion_medica": [
            "medico", "medica", "clinica", "hospital", "consulta", "doctor",
            "enfermero", "enfermeria", "cirugia", "tratamiento", "paciente",
            "especialista", "atencion medica", "farmacia", "farmaceutico",
            "diagnostico clinico", "historia clinica", "cita medica"
        ],
        "fitness_bienestar": [
            "gym", "gimnasio", "ejercicio", "entrenamiento", "yoga", "pilates",
            "crossfit", "atletismo", "cardio", "musculacion", "meditacion",
            "mindfulness", "coach", "instructor", "wellness", "spa", "aerobicos",
            "funcional", "estiramiento", "flexibilidad", "hiit"
        ],
        "mascotas": [
            "mascota", "perro", "gato", "can", "felino", "veterinario",
            "veterinaria", "petshop", "pet shop", "animal", "collar",
            "desparasitante", "canino", "acuario", "roedor", "ave",
            "alimento para mascotas", "juguete mascota", "vacuna animal"
        ],
        "medicina_natural": [
            "suplemento", "vitamina", "mineral", "omega", "colageno",
            "herbal", "fitoterapia", "homeopatia", "botanico", "extracto",
            "capsula", "tableta", "natural", "medicinal", "hierba",
            "esencial", "aceite esencial", "tinturas", "flores de bach"
        ],
        "productos_ecologicos": [
            "ecologico", "sostenible", "biodegradable", "reciclable",
            "eco", "sustentable", "libre de toxicos", "amigable",
            "huella de carbono", "compostable", "cero residuos", "verde",
            "reutilizable", "natural sin quimicos", "certificado organico"
        ],
        "tecnologia_medica": [
            "equipo medico", "dispositivo medico", "electromedico",
            "oximetro", "glucometro", "tensimetro", "monitor cardiaco",
            "ecografia", "laboratorio clinico", "instrumental quirurgico",
            "protesis", "ortesis", "rehabilitacion", "imagen medica",
            "telemedicina", "software medico", "camilla", "nebulizador"
        ]
    };

    // Sufijos legales peruanos que NO identifican a una empresa específica
    const SUFIJOS_LEGALES = new Set([
        "sac", "eirl", "sa", "srl", "srltda", "ltda", "spa", "bv", "scrl", "cia", "corp"
    ]);

    // Palabras muy frecuentes en razones sociales peruanas que por sí solas
    // no son identificadores únicos de una empresa concreta
    const PALABRAS_GENERICAS_EMPRESA = new Set([
        // Localizaciones y gentilicios
        "peru", "peruana", "peruano", "peruvian", "lima", "andina", "andino",
        "norte", "central", "nacional", "internacional",
        // Tipos de entidad comercial genérica
        "distribuidora", "distribuciones", "distribuidor", "distribuidores",
        "comercial", "comercializadora", "empresa", "corporacion",
        "grupo", "importadora", "exportadora", "inversiones",
        "industria", "industrial", "industrias",
        // Calificativos genéricos
        "super", "mega", "union", "unica", "unico", "nuevo", "nueva",
        "primera", "primero", "general"
    ]);

    function normalizarTexto(txt) {
        return (txt || "")
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9\s]/g, " ")
            .replace(/\s+/g, " ")
            .trim();
    }

    function extraerPalabrasClaveBase(input, data) {
        const base = normalizarTexto([
            input?.categoria,
            input?.descripcionActividad,   // descripción breve declarada por el usuario
            input?.nombre,
            data?.razonSocial,
            data?.nombreComercial,
            data?.actividad
        ].filter(Boolean).join(" "));

        const palabras = base.split(" ").filter(w => w.length >= 4);
        return [...new Set(palabras)];
    }

    function contarCoincidencias(texto, keywords) {
        const t = normalizarTexto(texto);
        return keywords.filter(k => t.includes(normalizarTexto(k))).length;
    }

    // Extrae las palabras que realmente identifican a la empresa (nombre propio),
    // descartando sufijos legales y términos genéricos del entorno empresarial peruano.
    // Ejemplos:
    //   "TIANSHI PERU S.A.C."       → ["tianshi"]
    //   "LABORATORIOS GARCIA S.A.C."→ ["laboratorios", "garcia"]
    //   "BAYER S.A."                → ["bayer"]
    //   "DISTRIBUIDORA NORTE S.A.C."→ []  ← sin palabras distintivas (se cubre por input.nombre)
    function extraerTokensDistintivos(texto) {
        return normalizarTexto(texto || "")
            .split(/\s+/)
            .filter(w => w.length >= 5
                && !SUFIJOS_LEGALES.has(w)
                && !PALABRAS_GENERICAS_EMPRESA.has(w));
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Detecta por MÚLTIPLES SEÑALES si el texto corresponde a la empresa registrada.
    //
    // Cubre los principales escenarios de informalidad en Perú:
    //   - Empresa que no registra nombre comercial en SUNAT (queda igual a razón social)
    //   - Empresa con marca pública distinta a su razón social  (TIANSHI PERU → TIENS)
    //   - Franquicias internacionales o acrónimos                (YVES ROCHER → YR)
    //   - Empresa cuyo catálogo/web muestra el RUC pero no el nombre completo
    //   - Dominios web que abrevian o combinan el nombre oficial
    //
    // Señales en orden de confiabilidad:
    //   1. Razón social SUNAT completa en el texto
    //   2. Nombre comercial SUNAT (si está registrado y difiere)
    //   3. Nombre/marca declarado por el usuario en el formulario
    //   4. RUC de la empresa (identificador único irrefutable)
    //   5. Palabras distintivas extraídas de la razón social
    // ─────────────────────────────────────────────────────────────────────────────
    function detectarVinculacionEmpresa(t, input, data) {
        // Señal 1: Razón social completa — la más confiable
        if (data?.razonSocial && t.includes(normalizarTexto(data.razonSocial)))
            return { detectada: true, señal: "razón social registrada en SUNAT" };

        // Señal 2: Nombre comercial SUNAT (solo si está registrado y difiere de la razón social)
        if (data?.nombreComercial && data.nombreComercial !== "-"
            && data.nombreComercial !== data.razonSocial
            && t.includes(normalizarTexto(data.nombreComercial)))
            return { detectada: true, señal: "nombre comercial en SUNAT" };

        // Señal 3: Nombre/marca declarado por el usuario en el formulario
        // Cubre empresas que operan con marca distinta a su razón social
        // Ej: el usuario declara "TIENS" aunque SUNAT diga "TIANSHI PERU S.A.C."
        const nombreNorm = normalizarTexto(input?.nombre || "");
        if (nombreNorm.length >= 4 && t.includes(nombreNorm))
            return { detectada: true, señal: `marca/nombre declarado "${input.nombre}"` };

        // Señal 4: RUC — identificador único irrefutable
        // Aparece en sitios web oficiales, PDFs, catálogos y fichas técnicas formales
        if (input?.ruc && t.includes(normalizarTexto(input.ruc)))
            return { detectada: true, señal: "RUC de la empresa encontrado en la evidencia" };

        // Señal 5: Palabras distintivas de la razón social (nombre propio sin sufijos ni genéricos)
        // Ej: "TIANSHI PERU S.A.C." → busca "tianshi" en el texto
        // Ej: "LABORATORIOS GARCIA S.A.C." → busca "laboratorios" o "garcia"
        const tokens = extraerTokensDistintivos(data?.razonSocial);
        const tokenEncontrado = tokens.find(p => t.includes(p));
        if (tokenEncontrado)
            return { detectada: true, señal: `nombre propio "${tokenEncontrado}" identificado en la evidencia` };

        return { detectada: false, señal: null };
    }

    function evaluarCoherenciaTexto(texto, input, data) {
        const t = normalizarTexto(texto);
        const baseKeywords = extraerPalabrasClaveBase(input, data);

        const coincidenciasBase = contarCoincidencias(t, baseKeywords);
        const coincidenciasComercio = contarCoincidencias(t, KEYWORDS_COMERCIO);

        // Detección multi-señal: cubre informalidad y marcas distintas a la razón social
        const vinculacion = detectarVinculacionEmpresa(t, input, data);
        const tieneNombreEmpresa = vinculacion.detectada;

        const tieneRubro = contarCoincidencias(t, KEYWORDS_RUBRO) >= 1;
        const tieneContacto = /(\b\d{7,}\b|@|whatsapp|facebook|instagram|tiktok|linkedin|www\.)/i.test(texto);
        const tieneEstructuraComercial = coincidenciasComercio >= 2;
        const tieneContenidoSuficiente = t.length >= 80;

        // Capa 2: Coherencia con la categoría específica declarada por el usuario
        // Si la evidencia menciona términos propios de la subcategoría elegida → +5 bonus
        // Si no, la evidencia sigue siendo válida por Capa 1 (rubro general); no resta puntos
        const keywordsCategoria = KEYWORDS_CATEGORIA[input?.categoria] || [];
        const coincidenciasCategoria = contarCoincidencias(t, keywordsCategoria);
        const tieneCategoria = keywordsCategoria.length > 0 && coincidenciasCategoria >= 1;

        let score = 0;

        if (tieneNombreEmpresa) score += 15;
        if (tieneRubro) score += 15;
        if (tieneEstructuraComercial) score += 10;
        if (tieneContacto) score += 5;
        if (coincidenciasBase >= 2) score += 5;
        if (tieneContenidoSuficiente) score += 5;
        if (tieneCategoria) score += 5;  // bonus por precisión de subcategoría

        return {
            score: Math.min(score, 55),  // techo ajustado: 50 base + 5 bonus categoría
            tieneNombreEmpresa,
            señalVinculacion: vinculacion.señal,
            tieneRubro,
            tieneCategoria,
            coincidenciasCategoria,
            tieneContacto,
            tieneEstructuraComercial,
            tieneContenidoSuficiente,
            coincidenciasBase,
            coincidenciasComercio
        };
    }

    function evaluarEvidenciaActividad(input, data) {
        const evidencia = input?.evidencia || {};
        const tipo = normalizarTexto(evidencia.tipo || "");
        const texto = evidencia.texto || evidencia.contenido || evidencia.ocr || evidencia.descripcion || "";
        const url = evidencia.valor || evidencia.url || "";

        // Diagnóstico inicial: muestra lo que el usuario declaró sobre su actividad
        // Esto aparece en el reporte antes del resultado de la evaluación de la evidencia
        const diagnosticoDeclaracion = [];
        if (input?.descripcionActividad) {
            diagnosticoDeclaracion.push(
                `El usuario declaró que su empresa se dedica a: ${input.descripcionActividad}`
            );
        }
        if (input?.categoria) {
            const etiquetasCategoria = {
                "alimentos_saludables": "Alimentos saludables / orgánicos",
                "atencion_medica":      "Atención médica",
                "fitness_bienestar":    "Fitness y bienestar físico",
                "mascotas":             "Mascotas",
                "medicina_natural":     "Medicina natural, suplementos y vitaminas",
                "productos_ecologicos": "Productos ecológicos",
                "tecnologia_medica":    "Tecnología médica"
            };
            const etiqueta = etiquetasCategoria[input.categoria] || input.categoria;
            diagnosticoDeclaracion.push(`Categoría declarada: ${etiqueta}`);
        }

        // Función auxiliar: antepone la declaración al diagnóstico final
        function conDeclaracion(resultado) {
            return {
                ...resultado,
                diagnostico: [...diagnosticoDeclaracion, ...resultado.diagnostico]
            };
        }

        // ── Verifica si el dominio de la URL está vinculado a la empresa registrada ──
        // Usa tokens distintivos (sin sufijos/genéricos) + nombre declarado.
        // Ej: "TIANSHI PERU S.A.C." con marca "TIENS" → reconoce "tiens.com.pe" o "tianshi.pe"
        // Ej: "YANBAL INTERNACIONAL S.A." → reconoce "yanbal.com"
        function dominioVinculadoEmpresa() {
            if (!url) return false;
            try {
                const dominio = normalizarTexto(new URL(url).hostname);
                const nombreNorm = normalizarTexto(input?.nombre || "");
                const tokensURL = [
                    ...extraerTokensDistintivos(data?.razonSocial),
                    ...nombreNorm.split(/\s+/).filter(w => w.length >= 4)
                ];
                return tokensURL.some(p => p.length >= 4 && dominio.includes(p));
            } catch { return false; }
        }

        // ── Regla central de puntuación ──────────────────────────────────────────────
        // Si la empresa NO está identificada en la evidencia → máximo +25 aunque el
        // rubro sea correcto, porque no se puede confirmar que la evidencia sea propia.
        // Si la empresa SÍ está identificada → puntaje completo según coherencia.
        // La detección usa múltiples señales (razón social, marca, RUC, dominio URL, etc.)
        // ─────────────────────────────────────────────────────────────────────────────
        function puntuarCoherencia(coherencia, tipoLabel, checkDominio) {
            // Para URLs: el dominio puede ser una señal adicional de vinculación
            let señalDominio = null;
            if (checkDominio && !coherencia.tieneNombreEmpresa && dominioVinculadoEmpresa()) {
                try {
                    señalDominio = `dominio "${normalizarTexto(new URL(url).hostname)}" vinculado a la empresa`;
                } catch {
                    señalDominio = "dominio URL vinculado a la empresa";
                }
            }

            const empresaIdentificada = coherencia.tieneNombreEmpresa || señalDominio !== null;
            // Para el diagnóstico: muestra exactamente qué señal identificó a la empresa
            const señalDetectada = coherencia.señalVinculacion || señalDominio;
            const detalleSeñal = señalDetectada ? ` · ${señalDetectada}` : "";

            // Sin relación con el rubro → rechazo inmediato independientemente de la empresa
            if (!coherencia.tieneRubro) {
                return {
                    score: 0,
                    diagnostico: [
                        `${tipoLabel}: sin coherencia con el rubro de salud/bienestar`,
                        "✗ No se encontraron términos del rubro en la evidencia presentada"
                    ],
                    rechazoInmediato: true
                };
            }

            if (empresaIdentificada) {
                // Empresa identificada: scoring completo según coherencia
                // El umbral sigue en 35; el bonus de categoría (+5) hace más fácil alcanzarlo
                // cuando la evidencia es precisa con la subcategoría declarada
                if (coherencia.score >= 35) {
                    const bonusCategoria = coherencia.tieneCategoria
                        ? " · evidencia coherente con la categoría declarada"
                        : "";
                    return {
                        score: 50,
                        diagnostico: [
                            `${tipoLabel}: alta coherencia con el rubro y empresa identificada (+50)${detalleSeñal}${bonusCategoria}`
                        ],
                        rechazoInmediato: false
                    };
                } else {
                    return {
                        score: 25,
                        diagnostico: [
                            `${tipoLabel}: empresa identificada, coherencia parcial con el rubro (+25)${detalleSeñal}`
                        ],
                        rechazoInmediato: false
                    };
                }
            } else {
                // Rubro presente pero empresa NO identificada → cap en +25
                const obsEmpresa =
                    "⚠ La evidencia no menciona el nombre de la empresa registrada " +
                    "ni contiene información vinculada directamente a ella (web, redes, razón social)";

                if (coherencia.score >= 25) {
                    return {
                        score: 25,
                        diagnostico: [
                            `${tipoLabel}: relacionada con el rubro de salud/bienestar ` +
                            `pero sin identificar a la empresa registrada (+25)`,
                            obsEmpresa
                        ],
                        rechazoInmediato: false
                    };
                } else if (coherencia.score >= 20) {
                    return {
                        score: 10,
                        diagnostico: [
                            `${tipoLabel}: coherencia parcial con el rubro ` +
                            `sin vinculación a la empresa registrada (+10)`,
                            obsEmpresa
                        ],
                        rechazoInmediato: false
                    };
                } else {
                    return {
                        score: 0,
                        diagnostico: [
                            `${tipoLabel}: coherencia insuficiente con el rubro y empresa no identificada`,
                            obsEmpresa
                        ],
                        rechazoInmediato: true
                    };
                }
            }
        }

        // ── TIPO: TEXTO ──
        if (tipo.includes("texto")) {
            if (!texto) {
                return conDeclaracion({ score: 0, diagnostico: ["Texto de evidencia vacío"], rechazoInmediato: true });
            }
            return conDeclaracion(puntuarCoherencia(evaluarCoherenciaTexto(texto, input, data), "Texto de evidencia", false));
        }

        // ── TIPO: URL / WEB / RED SOCIAL ──
        if (tipo.includes("url") || tipo.includes("web") ||
            tipo.includes("red social") || tipo.includes("social")) {
            if (!url) {
                return conDeclaracion({ score: 0, diagnostico: ["La evidencia URL no contiene enlace"], rechazoInmediato: true });
            }
            const textoUrl = normalizarTexto(`${url} ${texto}`);
            return conDeclaracion(puntuarCoherencia(evaluarCoherenciaTexto(textoUrl, input, data), "URL/red social", true));
        }

        // ── TIPO: CATÁLOGO PDF ──
        if (tipo.includes("catalogo") || tipo.includes("catálogo")) {
            if (!texto) {
                return conDeclaracion({ score: 0, diagnostico: ["El catálogo PDF no tiene texto extraído"], rechazoInmediato: true });
            }
            return conDeclaracion(puntuarCoherencia(evaluarCoherenciaTexto(texto, input, data), "Catálogo PDF", false));
        }

        // ── TIPO: FICHA TÉCNICA PDF ──
        if (tipo.includes("ficha")) {
            if (!texto) {
                return conDeclaracion({ score: 0, diagnostico: ["La ficha técnica PDF no tiene texto extraído"], rechazoInmediato: true });
            }
            const t = normalizarTexto(texto);
            const tieneEspecificacion =
                /(\bmedida\b|\bpeso\b|\bmaterial\b|\bmodelo\b|\bcapacidad\b|\bvoltaje\b|\bpotencia\b|\bcomposicion\b|\bcomponentes\b)/i.test(t);
            const coherencia = evaluarCoherenciaTexto(texto, input, data);
            const empresaIdentificada = coherencia.tieneNombreEmpresa;

            if (!coherencia.tieneRubro) {
                return conDeclaracion({
                    score: 0,
                    diagnostico: [
                        "Ficha técnica: sin coherencia con el rubro de salud/bienestar",
                        "✗ No se encontraron términos del rubro en la ficha técnica"
                    ],
                    rechazoInmediato: true
                });
            }

            const obsEmpresa = "⚠ La ficha técnica no menciona el nombre de la empresa registrada";

            if (empresaIdentificada) {
                if (coherencia.score >= 30 && tieneEspecificacion) {
                    const bonusCategoria = coherencia.tieneCategoria
                        ? " · coherente con la categoría declarada" : "";
                    return conDeclaracion({
                        score: 50,
                        diagnostico: [`Ficha técnica con especificaciones, rubro confirmado y empresa identificada (+50)${bonusCategoria}`],
                        rechazoInmediato: false
                    });
                } else {
                    return conDeclaracion({
                        score: 25,
                        diagnostico: ["Ficha técnica vinculada a la empresa con coherencia parcial (+25)"],
                        rechazoInmediato: false
                    });
                }
            } else {
                if (tieneEspecificacion && coherencia.score >= 25) {
                    return conDeclaracion({
                        score: 25,
                        diagnostico: [
                            "Ficha técnica relacionada con el rubro pero sin identificar a la empresa registrada (+25)",
                            obsEmpresa
                        ],
                        rechazoInmediato: false
                    });
                } else if (coherencia.score >= 20) {
                    return conDeclaracion({
                        score: 10,
                        diagnostico: [
                            "Ficha técnica con coherencia parcial con el rubro y sin vinculación a la empresa (+10)",
                            obsEmpresa
                        ],
                        rechazoInmediato: false
                    });
                } else {
                    return conDeclaracion({
                        score: 0,
                        diagnostico: [
                            "Ficha técnica sin coherencia suficiente con el rubro ni con la empresa registrada",
                            obsEmpresa
                        ],
                        rechazoInmediato: true
                    });
                }
            }
        }

        // ── TIPO: BOLETA / FACTURA PDF ──
        if (tipo.includes("boleta") || tipo.includes("factura")) {
            if (!texto) {
                return conDeclaracion({ score: 0, diagnostico: ["La boleta/factura PDF no tiene texto extraído"], rechazoInmediato: true });
            }
            const t = normalizarTexto(texto);
            const tieneComprobante = /boleta|factura|comprobante/i.test(t);
            const tieneRucDoc = input?.ruc
                ? t.includes(normalizarTexto(input.ruc))
                : /\b20\d{9}\b/.test(t);
            const tieneEmisor =
                t.includes(normalizarTexto(data?.razonSocial)) ||
                (data?.nombreComercial && data?.nombreComercial !== "-" &&
                    t.includes(normalizarTexto(data?.nombreComercial)));
            const tieneItems =
                /(\bitem\b|\bdescripcion\b|\bsubtotal\b|\bigv\b|\btotal\b|\bcantidad\b)/i.test(t);

            if (tieneComprobante && tieneRucDoc && tieneEmisor && tieneItems) {
                return conDeclaracion({
                    score: 50,
                    diagnostico: ["Boleta/factura válida y vinculada al negocio registrado (+50)"],
                    rechazoInmediato: false
                });
            } else if ((tieneComprobante && tieneRucDoc) || (tieneComprobante && tieneEmisor)) {
                const obs = [];
                if (!tieneEmisor) obs.push("⚠ El emisor del comprobante no coincide con la empresa registrada");
                if (!tieneRucDoc) obs.push("⚠ El RUC del comprobante no coincide con el RUC registrado");
                return conDeclaracion({
                    score: 25,
                    diagnostico: ["Boleta/factura parcialmente consistente con la empresa registrada (+25)", ...obs],
                    rechazoInmediato: false
                });
            } else {
                const obs = [];
                if (!tieneEmisor) obs.push("✗ El comprobante no pertenece a la empresa registrada");
                if (!tieneRucDoc) obs.push("✗ El RUC del comprobante no corresponde al RUC registrado");
                return conDeclaracion({
                    score: 0,
                    diagnostico: ["Boleta/factura no vinculada a la empresa registrada", ...obs],
                    rechazoInmediato: true
                });
            }
        }

        return conDeclaracion({
            score: 0,
            diagnostico: ["Tipo de evidencia no reconocido"],
            rechazoInmediato: true
        });
    }

    // ===== 6. EVIDENCIA DE ACTIVIDAD (máx +50) — LLAMADA =====
    const resultadoEvidencia = evaluarEvidenciaActividad(input, data);
    score += resultadoEvidencia.score;
    diagnostico.push(...resultadoEvidencia.diagnostico);
    rechazoInmediato = resultadoEvidencia.rechazoInmediato;

    // ===== CLASIFICACIÓN =====
    let estado;
    let riesgo;

    if (rechazoInmediato) {
        estado = "RECHAZADO";
        riesgo = "alto";
        diagnostico.push("Rechazo inmediato: evidencia no coherente con el rubro declarado");
    } else if (score >= 70) {
        estado = "ACEPTADO";
        riesgo = "bajo";
    } else if (score >= 45) {
        estado = "REVISION";
        riesgo = "medio";
    } else {
        estado = "RECHAZADO";
        riesgo = "alto";
    }

    return {
        estado,
        score,
        riesgo,
        rechazoInmediato,
        diagnostico
    };
}


// ===== EVALUACIÓN COMPLETA =====

function evaluarEmpresa(input, data) {
    const booleana = evaluacionBooleana(data);

    if (!booleana.aprobada) {
        return {
            etapa: 1,
            estado: "RECHAZADO",
            riesgo: "alto",
            score: 0,
            diagnostico: booleana.rechazos
        };
    }

    const puntaje = evaluacionPuntaje(input, data);

    return {
        etapa: 2,
        ...puntaje
    };
}

module.exports = { evaluarEmpresa };