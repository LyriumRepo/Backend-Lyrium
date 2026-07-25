{{--
    contratos/tyc-seller.blade.php
    ─────────────────────────────────────────────────────────────────────────
    Vista COMPLETA de los Términos y Condiciones Generales aplicables a los
    Sellers de Lyrium Biomarketplace. Documento genérico — no depende de
    datos del formulario del vendedor (a diferencia del preview de Cláusulas
    Monetarias). Se genera al vuelo en cada apertura del modal, vía
    GET /api/contracts/terms — no se guarda ningún archivo.

    Incluye el texto íntegro de las 17 cláusulas (PRIMERO a DÉCIMO SÉPTIMA),
    incluyendo la tabla de comisiones de la Cláusula QUINTA.

    CORRECCIONES DE NUMERACIÓN APLICADAS (documentadas para el cliente):
    1. Cláusula Primera, inciso 1.3: el documento original etiqueta como "f.)"
       al párrafo de los planes (Emprende/Especial/Crece), justo después del
       inciso "k.)" que ya anticipa textualmente "conforme al literal l) de
       la presente Cláusula". Se corrigió la etiqueta a "l.)" para que las
       referencias cruzadas de las cláusulas 4.1-a, 8.1 y 8.3-iii tengan
       sentido.
    2. Cláusula 4.2: el original repite la numeración "4.2.1." en dos
       párrafos distintos. Se renumeró la segunda serie de forma correlativa
       (4.2.2 a 4.2.7) para evitar duplicados.
    3. Cláusula QUINTA: el original repite "5.1." y "5.2." después de la
       tabla de comisiones. Se renumeró la segunda serie como 5.3 y 5.4 a
       5.6 (ver detalle en el cuerpo del documento).

    Ninguna de estas correcciones altera el contenido o significado de las
    cláusulas — únicamente su numeración, para que sea internamente
    consistente y no genere confusión al Seller.
--}}

<style>
    .lyrium-contrato {
        --lc-text:          #1a1a1a;
        --lc-text-muted:    #555555;
        --lc-text-faint:    #888888;
        --lc-accent:        #4a7a5a;
        --lc-accent-head:   #1a3a2a;
        --lc-bg-lyrium:     #f8faf8;
        --lc-bg-seller:     #f0f7f0;
        --lc-border-head:   #1a3a2a;
        --lc-border-card:   #d0e8d0;
        --lc-border-seller: #4a7a5a;
        --lc-border-foot:   #cccccc;
        --lc-highlight:     #f0f7f0;
        --lc-link:          #4a7a5a;
        --lc-clausula-border: #4a7a5a;
        --lc-table-header-bg: #eef5ef;
        --lc-table-border:    #d5e5d8;
        --lc-table-row-alt:   #f8faf8;
        --lc-btn-bg:          #4a7a5a;
        --lc-btn-text:        #ffffff;
        --lc-btn-bg-hover:    #3d6a4c;
    }

    .dark .lyrium-contrato {
        --lc-text:          #e2e8e4;
        --lc-text-muted:    #9ab5a2;
        --lc-text-faint:    #6b8f78;
        --lc-accent:        #8FC3A1;
        --lc-accent-head:   #8FC3A1;
        --lc-bg-lyrium:     rgba(143,195,161,0.05);
        --lc-bg-seller:     rgba(143,195,161,0.09);
        --lc-border-head:   #8FC3A1;
        --lc-border-card:   rgba(143,195,161,0.2);
        --lc-border-seller: #8FC3A1;
        --lc-border-foot:   rgba(143,195,161,0.2);
        --lc-highlight:     rgba(143,195,161,0.12);
        --lc-link:          #8FC3A1;
        --lc-clausula-border: #8FC3A1;
        --lc-table-header-bg: rgba(143,195,161,0.12);
        --lc-table-border:    rgba(143,195,161,0.22);
        --lc-table-row-alt:   rgba(143,195,161,0.05);
        --lc-btn-bg:          #8FC3A1;
        --lc-btn-text:        #10241a;
        --lc-btn-bg-hover:    #7bb090;
    }

    .lyrium-contrato {
        font-family: 'Georgia', 'Times New Roman', serif;
        font-size: 12px;
        line-height: 1.7;
        color: var(--lc-text);
        max-width: 100%;
    }

    .lc-header {
        text-align: center;
        margin-bottom: 18px;
        padding-bottom: 16px;
        border-bottom: 2px solid var(--lc-border-head);
    }
    .lc-header-brand {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--lc-accent);
        margin: 0 0 4px;
    }
    .lc-header-title {
        font-size: 14px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
        color: var(--lc-text);
    }
    .lc-header-sub {
        font-size: 11px;
        font-style: italic;
        color: var(--lc-text-muted);
        margin: 6px 0 0;
    }

    .lc-p        { margin: 0 0 14px; text-align: justify; }

    .lc-clausula {
        font-size: 12.5px;
        font-weight: bold;
        margin: 22px 0 10px;
        color: var(--lc-accent-head);
        border-left: 3px solid var(--lc-clausula-border);
        padding-left: 8px;
    }
    .lc-sub-clausula {
        font-size: 12px;
        font-weight: bold;
        margin: 16px 0 8px;
        color: var(--lc-accent-head);
    }

    .lc-item-num {
        margin: 0 0 10px;
        text-align: justify;
        padding-left: 32px;
        text-indent: -32px;
    }
    .lc-num {
        font-weight: bold;
        color: var(--lc-accent-head);
        margin-right: 2px;
    }

    .lc-letter-item {
        margin: 6px 0 6px 20px;
        text-align: justify;
        padding-left: 20px;
        text-indent: -20px;
    }
    .lc-roman-item {
        margin: 6px 0;
        text-align: justify;
        padding-left: 38px;
        text-indent: -38px;
    }

    .lc-bullets {
        margin: 6px 0 12px 20px;
        padding: 0;
        list-style: disc;
    }
    .lc-bullets li { margin: 4px 0; text-align: justify; }

    .lc-table-wrap { margin: 10px 0 18px; overflow-x: auto; }
    .lc-table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
    .lc-table th {
        background: var(--lc-table-header-bg);
        color: var(--lc-accent-head);
        font-weight: bold;
        text-align: left;
        padding: 8px 10px;
        border: 1px solid var(--lc-table-border);
    }
    .lc-table td {
        padding: 7px 10px;
        border: 1px solid var(--lc-table-border);
        color: var(--lc-text);
    }
    .lc-table tbody tr:nth-child(even) { background: var(--lc-table-row-alt); }

    .lc-footer {
        margin-top: 28px;
        padding-top: 16px;
        border-top: 1px solid var(--lc-border-foot);
    }
    .lc-footer-text {
        margin: 0 0 8px;
        text-align: center;
        font-size: 11px;
        color: var(--lc-text-muted);
    }

    .lc-toc {
        margin: 0 0 20px;
        padding: 14px 18px;
        background: var(--lc-bg-lyrium);
        border: 1px solid var(--lc-border-card);
        border-radius: 8px;
        font-size: 11px;
    }
    .lc-toc-title {
        font-weight: bold;
        color: var(--lc-accent-head);
        margin: 0 0 8px;
        text-transform: uppercase;
        font-size: 10px;
        letter-spacing: 0.5px;
    }
    .lc-toc ol {
        margin: 0;
        padding-left: 18px;
        columns: 1;
    }
    .lc-toc li { margin: 3px 0; color: var(--lc-text-muted); }
</style>

<div class="lyrium-contrato">

    {{-- ── Encabezado ──────────────────────────────────────────────────────── --}}
    <div class="lc-header">
        <p class="lc-header-brand">Lyrium Biomarketplace</p>
        <h2 class="lc-header-title">Términos y Condiciones</h2>
        <p class="lc-header-sub">Aplicables a los Sellers de Lyrium Biomarketplace</p>
    </div>

    {{-- ── Índice ──────────────────────────────────────────────────────────── --}}
    <div class="lc-toc">
        <p class="lc-toc-title">Contenido</p>
        <ol>
            <li>Antecedentes generales</li>
            <li>Alcance de los Términos y Condiciones Generales</li>
            <li>Utilización de la plataforma virtual y prestación de los servicios</li>
            <li>Comercialización de Productos y Servicios en el Sitio Web</li>
            <li>Comisión</li>
            <li>Cancelaciones, devoluciones, cambio, garantía, reembolsos y reprogramaciones</li>
            <li>Propiedad intelectual</li>
            <li>Resolución del Acuerdo y eliminación de cuenta del Seller</li>
            <li>Responsabilidad</li>
            <li>Vigencia</li>
            <li>Comunicaciones</li>
            <li>Cesión del contrato y los Términos</li>
            <li>Protección y tratamiento de datos personales</li>
            <li>Confidencialidad</li>
            <li>Relación entre las partes</li>
            <li>Publicidad de la plataforma</li>
            <li>Ley aplicable y solución de controversias</li>
        </ol>
    </div>

    {{-- ════════════════════════ 1. PRIMERO ════════════════════════ --}}
    <p class="lc-clausula">Primero: Antecedentes Generales</p>

    <p class="lc-item-num"><span class="lc-num">1.1.</span> LYRIUM E.I.R.L. (en adelante, "Lyrium") es una empresa constituida bajo las leyes de la República del Perú, cuya actividad principal comprende la comercialización de productos y servicios de terceros vinculados al sector de bienestar y salud. Con este propósito, Lyrium ha desarrollado una plataforma propia denominada "Lyrium Biomarketplace", disponible en el dominio de su titularidad exclusiva: www.lyriumbiomarketplace.com (en adelante, "La Plataforma web").</p>

    <p class="lc-p">Lyrium Biomarketplace está diseñada para ser utilizada por proveedores especializados en el rubro de bienestar y salud (denominados individualmente "Seller" y en conjunto "Sellers"), quienes, previa autorización de Lyrium, quedan habilitados para comercializar directamente en el Perú sus propios productos y servicios a los Clientes que visiten la Plataforma web, bajo su exclusiva cuenta y riesgo.</p>

    <p class="lc-item-num"><span class="lc-num">1.2.</span> Una vez que Lyrium autorice e incorpore al Seller como proveedor registrado, este tendrá acceso a la plataforma Lyrium Biomarketplace, la cual le permitirá:</p>
    <p class="lc-roman-item">(i) Publicar, ofrecer y vender sus propios productos y servicios a Clientes de nivel nacional.</p>
    <p class="lc-roman-item">(ii) Gestionar las órdenes de compra generadas por los consumidores respecto de uno o más productos y/o servicios de su tienda.</p>
    <p class="lc-roman-item">(iii) Administrar y monitorear el flujo de órdenes recibidas, identificando los productos y servicios requeridos y accediendo a la información relevante de cada Cliente.</p>

    <p class="lc-item-num"><span class="lc-num">1.3.</span> Para garantizar una adecuada operación comercial dentro de la plataforma y bajo los estándares definidos por Lyrium, este prestará a los Sellers los siguientes servicios (en adelante, los "Servicios"):</p>

    <p class="lc-letter-item">a.) Facilitar al Seller un espacio virtual que le permita promocionar sus productos y/o servicios e impulsar el crecimiento de sus ventas de manera ágil y eficiente.</p>
    <p class="lc-letter-item">b.) Proporcionar al Seller un panel de control desde el cual pueda administrar y gestionar de forma integral la venta de sus productos y/o servicios dentro de la plataforma.</p>
    <p class="lc-letter-item">c.) Coordinar el servicio de postventa vinculado a los productos del Seller, abarcando, cuando corresponda, procesos de cancelación, devolución, cambio, garantía o reembolso. Sin perjuicio de ello, el Seller reconoce que la atención oportuna, adecuada y correcta de toda solicitud postventa asociada a sus productos es de su exclusiva responsabilidad. Lyrium no asumirá responsabilidad alguna por las decisiones, gestiones o actos relacionados con dichos procesos, limitando su participación a labores de coordinación según corresponda.</p>
    <p class="lc-letter-item">d.) Proponer políticas y lineamientos orientados a la mejora continua en materia de calidad, innovación y servicio postventa, con el objetivo de potenciar el desempeño de los Sellers dentro de la plataforma.</p>
    <p class="lc-letter-item">e.) Garantizar la protección del entorno digital de Lyrium Biomarketplace para la comercialización de los Productos y/o Servicios publicados por el Seller, mediante la implementación de medidas técnicas, administrativas y de seguridad informática acordes con las buenas prácticas de la industria, incluyendo mecanismos de autenticación, cifrado, monitoreo y protección de datos que salvaguarden las operaciones realizadas dentro de la plataforma. Dicha garantía será aplicable exclusivamente a las operaciones efectuadas dentro del ecosistema de Lyrium Biomarketplace. En consecuencia, cualquier redirección, enlace o utilización de plataformas, sitios web o medios de pago externos no autorizados por Lyrium eximirá a este de toda responsabilidad por los riesgos, pérdidas o daños que pudieran derivarse, constituyendo además causal automática de resolución de la relación contractual.</p>
    <p class="lc-letter-item">f.) Desarrollar campañas globales de marketing y publicidad orientadas a posicionar Lyrium Biomarketplace en el mercado, las cuales podrán coordinarse con los Sellers a fin de diseñar acciones más efectivas que también contribuyan a la visibilidad de cada tienda y sus productos o servicios.</p>
    <p class="lc-letter-item">g.) Brindar al Seller una capacitación inicial sobre el uso y funcionamiento de la plataforma Lyrium Biomarketplace, con el objetivo de facilitar su incorporación y operación dentro del ecosistema Lyrium.</p>
    <p class="lc-letter-item">h.) Gestionar la recaudación de los pagos efectuados por los Clientes a través de la plataforma, asegurando el procesamiento seguro de cada transacción.</p>
    <p class="lc-letter-item">i.) Generar y emitir, por cada operación de compra efectivamente concretada por un Cliente respecto de los productos y/o servicios del Seller, la factura, en el cual se consigne de manera separada la comisión aplicada por el uso de la plataforma. Este comprobante será remitido automáticamente al correo electrónico registrado por el Seller al momento de su ingreso en Lyrium Biomarketplace.</p>
    <p class="lc-letter-item">j.) Liquidar semanalmente al Seller el importe correspondiente a sus ventas del período anterior. Cada lunes se revisarán las ventas realizadas durante la semana inmediata anterior y el monto resultante, deducida la comisión de Lyrium, será transferido a la cuenta bancaria registrada por el Seller en un plazo máximo de 3 (tres) días hábiles, es decir, hasta el miércoles de cada semana, mediante transferencia bancaria, BCP Banca Móvil o a través de cajero automático.</p>
    <p class="lc-letter-item">k.) Otorgar los beneficios complementarios comprendidos en el plan que haya sido contratado o adquirido por el Seller, incluyendo, de manera enunciativa mas no limitativa, los previstos en el Plan Emprende, el cual se constituye como plan gratuito y por defecto con el que todo Seller inicia su participación en la plataforma Lyrium Biomarketplace. Sin perjuicio de ello, se deja expresa constancia de que la comisión por ventas aplicable a cada uno de los planes es la misma y constituye un cargo a favor de Lyrium Biomarketplace, conforme a lo establecido en los presentes Términos y Condiciones Generales para Sellers, en el Acuerdo suscrito por el Seller y en sus respectivos anexos. La vigencia de cada plan, así como la exigencia de venta mínima que corresponda, se regirán conforme a lo dispuesto en el literal l) de la presente Cláusula.</p>
    <p class="lc-letter-item">l.) Lyrium ofrece los siguientes planes de participación dentro de la plataforma Lyrium Biomarketplace:</p>

    <ul class="lc-bullets">
        <li><strong>Plan Emprende:</strong> plan gratuito y por defecto con el que todo Seller inicia su participación en la plataforma. Tiene una vigencia de 12 (doce) meses.</li>
        <li><strong>Plan Especial:</strong> plan gratuito, que cuenta con los mismos beneficios del Plan Crece, con excepción de la posibilidad de elegir su temporalidad, ya que su vigencia es fija de 6 (seis) meses. Este plan solo podrá ser adquirido una única vez por cada Seller.</li>
        <li><strong>Plan Crece:</strong> plan de pago que el Seller podrá adquirir en la temporalidad de su elección: 1 (uno), 2 (dos), 3 (tres) o 4 (cuatro) años, o de forma indefinida.</li>
    </ul>

    <p class="lc-p">Todos los planes se encuentran sujetos al cumplimiento de la venta mínima mensual establecida por Lyrium, equivalente a S/ 350.00 (trescientos cincuenta soles) en comercialización de Productos y a S/ 450.00 (cuatrocientos cincuenta soles) en prestación de Servicios, salvo por un período de gracia único de 6 (seis) meses, contado por única vez desde la fecha en que el Seller obtiene su reconocimiento como vendedor registrado en Lyrium Biomarketplace, independientemente del plan bajo el cual se haya registrado inicialmente. Vencido dicho período de gracia, la exigencia de venta mínima resultará aplicable de forma inmediata y continua, incluso si el Seller renueva el Acuerdo, cambia de plan o adquiere el Plan Crece, sin que corresponda el otorgamiento de un nuevo período de gracia.</p>

    <p class="lc-p">Toda referencia contenida en los presentes Términos y Condiciones Generales para Sellers y en el Acuerdo a la vigencia del plan o a la exigencia de venta mínima deberá entenderse sujeta a lo aquí establecido.</p>

    {{-- ════════════════════════ 2. SEGUNDO ════════════════════════ --}}
    <p class="lc-clausula">Segundo: Alcance de los Términos y Condiciones Generales</p>

    <p class="lc-item-num"><span class="lc-num">2.1.</span> Los presentes Términos y Condiciones Generales aplicables a los Sellers de lyriumbiomarketplace.com rigen la totalidad de los acuerdos específicos que se suscriban entre Lyrium y cada Seller para el uso de la plataforma virtual y la recepción de sus servicios, formando parte integrante de dichos acuerdos para todos los efectos legales que correspondan. Cada acuerdo suscrito entre Lyrium y un Seller será denominado en adelante el "Acuerdo".</p>

    <p class="lc-item-num"><span class="lc-num">2.2.</span> Con la suscripción del Acuerdo, el Seller manifiesta haber leído, comprendido y aceptado en su totalidad las condiciones aquí establecidas, constituyendo dicho acto un requisito indispensable para dar inicio a la relación contractual con Lyrium, así como para la comercialización de sus Productos y/o Servicios a través de su tienda en Lyrium Biomarketplace.</p>

    <p class="lc-p">Asimismo, el Seller acepta de manera anticipada que cualquier modificación que Lyrium introduzca a los presentes Términos y Condiciones le será aplicable automáticamente, entendiéndose incorporada al Acuerdo al día siguiente de su comunicación, ya sea mediante la plataforma o a través de correo electrónico dirigido a su Administrador del Acuerdo. Si el Seller no estuviera conforme con las modificaciones realizadas, podrá resolver el Acuerdo, lo que conllevará el cierre de su cuenta como Seller dentro de la plataforma.</p>

    <p class="lc-item-num"><span class="lc-num">2.3.</span> El Seller es consciente que tanto los presentes Términos y Condiciones como las estipulaciones del Acuerdo tienen aplicación exclusiva dentro del territorio de la República del Perú, limitándose su alcance a la comercialización de Productos y/o Servicios a través de la plataforma Lyrium Biomarketplace en el mercado nacional.</p>

    {{-- ════════════════════════ 3. TERCERO ════════════════════════ --}}
    <p class="lc-clausula">Tercero: Utilización de la Plataforma Virtual y Prestación de los Servicios</p>

    <p class="lc-item-num"><span class="lc-num">3.1.</span> Sujeto a la suscripción del Acuerdo, Lyrium autorizará al Seller para que se registre como proveedor habilitado para comercializar, a su tienda virtual, mediante el uso de Lyrium Biomarketplace, única y exclusivamente los productos y/o servicios de su titularidad que hayan sido previamente aprobados por Lyrium y que se encuentren expresamente detallados en el Acuerdo, en adelante, los "Productos y Servicios".</p>

    <p class="lc-p">Asimismo, Lyrium prestará al Seller los servicios que correspondan conforme a lo establecido en el Acuerdo y en los presentes Términos y Condiciones Generales para Sellers. Por su parte, el Seller se obliga a ofertar, comercializar y/o vender a través de su tienda virtual, brindada y por intermedio de Lyrium Biomarketplace, únicamente los Productos y Servicios de su titularidad autorizados por Lyrium.</p>

    <p class="lc-item-num"><span class="lc-num">3.2.</span> El Seller se obliga a cumplir de manera estricta, íntegra y oportuna con todas las obligaciones derivadas de los presentes Términos y Condiciones Generales para Sellers, del Acuerdo y de la normativa legal, administrativa, tributaria, sectorial y reglamentaria que resulte aplicable a la comercialización de los Productos y Servicios, debiendo actuar en todo momento con la diligencia debida exigible a un proveedor profesional.</p>

    <p class="lc-item-num"><span class="lc-num">3.3.</span> El Seller declara, garantiza y asegura a Lyrium que cuenta con la organización, experiencia, capacidad técnica, operativa, administrativa, financiera y logística necesarias, así como con la infraestructura, recursos humanos, materiales y demás medios suficientes para comercializar los Productos y Servicios a través de su tienda virtual, mediante Lyrium Biomarketplace, de forma íntegra, autónoma e independiente, y bajo su exclusiva cuenta, costo y riesgo, cumpliendo en tiempo, forma y calidad con todas las obligaciones asumidas, incluyendo, sin limitarse a ello, la atención y cumplimiento de las órdenes de compra que reciba. El Seller reconoce que las anteriores declaraciones y garantías han sido determinantes para la celebración del Acuerdo por parte de Lyrium.</p>

    {{-- ════════════════════════ 4. CUARTO ════════════════════════ --}}
    <p class="lc-clausula">Cuarto: Comercialización de Productos y Servicios en el Sitio Web a través de la Plataforma Lyrium Biomarketplace</p>

    <p class="lc-sub-clausula">4.1 Generalidades</p>

    <p class="lc-item-num"><span class="lc-num">4.1.1.</span> Toda transacción de compraventa o prestación de servicios que se origine a través de la tienda del Seller en Lyrium Biomarketplace se perfecciona de manera directa entre el Seller y el Cliente. Lyrium no forma parte de dicho vínculo contractual ni interviene como parte en ningún contrato que pudiera derivarse de la adquisición de Productos y/o Servicios ofrecidos por el Seller. Por tanto, las obligaciones y derechos emergentes de cada transacción vinculan exclusivamente al Seller y al Cliente correspondiente.</p>

    <p class="lc-p">En este marco, recae de manera íntegra y exclusiva sobre el Seller el cumplimiento de todas las obligaciones establecidas en la Ley N° 29571 — Código de Protección y Defensa del Consumidor, así como en cualquier norma concordante, complementaria, modificatoria y/o sustitutoria que se encuentre vigente o que sea aprobada en el futuro (en adelante, el "CPC"). Entre dichas obligaciones se incluyen, a modo enunciativo y no limitativo, las siguientes:</p>

    <p class="lc-letter-item">a.) Alcanzar la venta mínima establecida por Lyrium, una vez vencido el período de gracia único a que se refiere el literal l) de la Cláusula Primera.</p>
    <p class="lc-letter-item">b.) Responder por la idoneidad, calidad, seguridad y conformidad de los Productos y/o Servicios publicados en su tienda dentro de la plataforma; por la autenticidad de las marcas, rótulos, leyendas y demás elementos distintivos que exhiban; por la coherencia entre la publicidad comercial y las características reales del producto; así como por el contenido, estado, vigencia y vida útil de cada Producto.</p>
    <p class="lc-letter-item">c.) Poner a disposición de los Clientes, a través de su tienda en la plataforma, información redactada en idioma castellano que sea veraz, oportuna, suficiente, comprensible, visible y fácilmente accesible, de modo que les permita tomar decisiones de consumo informadas y hacer un uso adecuado de los Productos y/o Servicios adquiridos.</p>
    <p class="lc-letter-item">d.) Abstenerse de incluir o transmitir a los consumidores, a través de su tienda en Lyrium Biomarketplace o de cualquier otro canal vinculado a la comercialización, información que pudiera inducir a error en relación con la naturaleza, origen, modo de fabricación, componentes, usos, volumen, peso, medidas, precio, forma de empleo, características, propiedades, idoneidad, cantidad, calidad u otras características relevantes de los Productos.</p>
    <p class="lc-letter-item">e.) Mostrar de manera destacada y en moneda nacional peruana (S/.), el precio total de los Productos y/o Servicios, incluyendo los tributos, comisiones y cargos que resulten aplicables. El Cliente no podrá ser requerido a asumir pagos o recargos adicionales fuera del precio informado, salvo que se trate de servicios accesorios como transporte, instalación u otros de naturaleza similar, cuyo costo no esté comprendido en dicho precio.</p>
    <p class="lc-letter-item">f.) Facilitar a los consumidores, cuando corresponda, información sobre ingredientes, componentes, condiciones de garantía, manuales de uso, advertencias, riesgos previsibles y medidas a adoptar en caso de daño derivado del uso o consumo de los Productos.</p>
    <p class="lc-letter-item">g.) Cuando se trate de Productos cuya producción, fabricación, ensamble, importación, distribución o comercialización no contemple el suministro oportuno de partes, accesorios o servicios de reparación y mantenimiento o en los que dichos suministros se brinden con limitaciones, el Seller deberá informarlo al consumidor de manera clara e inequívoca antes de concretar la transacción.</p>
    <p class="lc-letter-item">h.) Comunicar de forma previa, clara y suficiente las condiciones, restricciones y requisitos de acceso aplicables a los Productos y/o Servicios ofrecidos en su tienda dentro de la plataforma.</p>
    <p class="lc-letter-item">i.) Abstenerse de emplear métodos, mecanismos contractuales y/o comerciales de carácter abusivo en el marco de sus operaciones comerciales.</p>
    <p class="lc-letter-item">j.) Desarrollar toda transacción comercial bajo estándares de trato justo, honesto, empático, transparente y equitativo, sin incurrir en ningún tipo de acto o práctica discriminatoria hacia los Clientes.</p>
    <p class="lc-letter-item">k.) Reponer, reparar y/o devolver los Productos adquiridos por los Clientes en cumplimiento de lo dispuesto en el CPC, garantizando una atención diligente y el uso de componentes o repuestos nuevos y adecuados para cada caso.</p>
    <p class="lc-letter-item">l.) Atender y dar respuesta a los reclamos presentados por sus Clientes dentro de los plazos legales aplicables. Con este propósito, Lyrium pondrá a disposición de los Clientes, dentro de la plataforma, el libro de reclamaciones correspondiente a cada Seller, en cumplimiento del Reglamento del Libro de Reclamaciones aprobado mediante Decreto Supremo N° 011-2011-PCM o de cualquier norma que lo modifique. La gestión, contenido, oportunidad y procedencia de cada reclamo es responsabilidad exclusiva del Seller, dado que la relación de consumo se establece directamente entre este y el consumidor, sin que Lyrium forme parte de dicha relación.</p>
    <p class="lc-letter-item">m.) En el caso de empresas prestadoras de servicios, el Seller deberá ejecutar el servicio contratado en la fecha y hora pactadas, coordinando directamente con el Cliente cualquier ajuste o reprogramación que resulte necesaria, a fin de garantizar una experiencia satisfactoria dentro de la plataforma Lyrium Biomarketplace.</p>

    <p class="lc-item-num"><span class="lc-num">4.1.2.</span> Los Productos y/o Servicios que el Seller publique y comercialice a través de su tienda en Lyrium Biomarketplace deberán ser, en todos los casos, productos nuevos y servicios ejecutados con implementos nuevos, cuya comercialización esté expresamente permitida por la legislación vigente de la República del Perú, cumpliendo con la totalidad de disposiciones legales aplicables para su debida oferta en el mercado.</p>

    <p class="lc-item-num"><span class="lc-num">4.1.3.</span> El Seller tendrá acceso a la plataforma Lyrium Biomarketplace mediante el correo corporativo o el correo de su preferencia registrado, así como la contraseña creada en el formulario de registro para Sellers de Lyrium. El Seller se obliga a utilizar dicho correo y contraseña únicamente para la comercialización de los Productos y/o Servicios, y a cumplir en todo momento con los lineamientos y directrices de uso de la plataforma, los cuales declara conocer y aceptar con la suscripción del Contrato. El correo corporativo, la contraseña y toda la información a la que el Seller tenga acceso en la plataforma con ocasión del Acuerdo y de la comercialización de los Productos y/o Servicios tendrán la condición de información confidencial, conforme a lo dispuesto en la Cláusula Décimo Cuarta de estos Términos y Condiciones Generales para Sellers.</p>

    <p class="lc-item-num"><span class="lc-num">4.1.4.</span> Como condición para la activación completa de su cuenta comercial y la habilitación de su panel de vendedor, el Seller acepta participar en el programa de difusión inicial de Lyrium Biomarketplace, comprometiéndose a compartir el enlace de invitación proporcionado por Lyrium a través de sus redes sociales, aplicaciones de mensajería u otros medios digitales equivalentes, hasta alcanzar el nivel máximo de difusión establecido por Lyrium, equivalente a quince (15) difusiones.</p>

    <p class="lc-sub-clausula">4.2 Entrega de información</p>

    <p class="lc-item-num"><span class="lc-num">4.2.1.</span> Quienes deseen incorporarse como Sellers en Lyrium Biomarketplace deberán completar el formulario de registro habilitado para tal efecto, proporcionando la información de su empresa o negocio, incluyendo RUC, nombre comercial, domicilio legal y demás datos requeridos, la cual será validada de forma automatizada por la plataforma al momento del registro. Adicionalmente, el solicitante deberá acreditar que su actividad comercial se encuentra vinculada a rubros de bienestar, salud, cuidado personal o afines, para lo cual podrá presentar la URL de su página web o red social, catálogo de productos o servicios, ficha técnica, factura o boleta reciente, u otro medio idóneo disponible en el formulario de registro.</p>

    <p class="lc-p">Una vez completado el formulario, el solicitante podrá revisar los presentes Términos y Condiciones, así como el Acuerdo Comercial de Prestación de Servicios de Lyrium Biomarketplace, el cual será generado con la información registrada y deberá ser suscrito tras la aceptación de ambos documentos. Según la información proporcionada, se aplicará uno de los siguientes resultados:</p>

    <p class="lc-letter-item">a) Si la información es correcta y todo está bien con ella, el solicitante será incorporado automáticamente como Seller de Lyrium Biomarketplace.</p>
    <p class="lc-letter-item">b) Si la información es parcialmente correcta, la solicitud quedará en estado de revisión manual, a la espera de comunicación por parte del área de administración de Lyrium.</p>
    <p class="lc-letter-item">c) Si la información es incorrecta, por ejemplo, si el RUC no existe, se encuentra inactivo, entre otros escenarios, la solicitud del Usuario para convertirse en Seller será rechazada de manera cordial por la plataforma.</p>

    <p class="lc-p">En los casos de revisión manual o rechazo, el solicitante podrá optar por recibir una notificación al correo electrónico registrado, en la que se indicará el motivo del estado asignado a su solicitud con mayor detalle.</p>

    <p class="lc-p">Con posterioridad al registro, cada vez que el Seller desee publicar o actualizar un Producto y/o Servicio, Lyrium llevará a cabo un proceso de verificación para determinar si dicho Producto y/o Servicio puede ser comercializado dentro de la plataforma.</p>

    <p class="lc-item-num"><span class="lc-num">4.2.2.</span> Mantener actualizada la información de los Productos y/o Servicios publicados en su tienda es una responsabilidad exclusiva del Seller, quien deberá realizar las actualizaciones que correspondan de manera oportuna. Los cambios de precio y de stock entrarán en vigor únicamente una vez que Lyrium confirme que la modificación ha sido procesada satisfactoriamente. Cualquier otra modificación sobre la información de los Productos y/o Servicios requerirá aprobación previa por parte de Lyrium.</p>

    <p class="lc-p">Cuando el Seller modifique la información de sus Productos y/o Servicios, los cambios de precio y de stock serán efectivos una vez que Lyrium le comunique que la modificación ha sido procesada satisfactoriamente. Cualquier otro cambio en la información de los Productos y/o Servicios deberá contar con la aprobación previa de Lyrium.</p>

    <p class="lc-p">El Seller declara conocer que ninguna actualización o corrección de información, incluyendo aquellas derivadas de errores propios, afectará las transacciones ya concretadas antes de la recepción del correo de confirmación emitido por Lyrium.</p>

    <p class="lc-item-num"><span class="lc-num">4.2.3.</span> Todo el contenido que el Seller publique en su tienda dentro de Lyrium Biomarketplace deberá ajustarse a los lineamientos y directrices establecidos por Lyrium. A modo enunciativo y no limitativo, queda prohibido: realizar publicidad falsa o engañosa sobre los Productos y/o Servicios; publicar precios o descripciones incorrectas; ofrecer Productos y/o Servicios no disponibles; efectuar publicidad comparativa o peyorativa respecto de los Productos y/o Servicios de Lyrium o de otros Sellers dentro o fuera de la plataforma; o atentar de cualquier forma contra la imagen o identidad de otros Sellers o de Lyrium.</p>

    <p class="lc-item-num"><span class="lc-num">4.2.4.</span> El Seller se abstiene de manipular su tienda o de incentivar, promover o inducir, por cualquier medio, la redirección de ventas iniciadas dentro de Lyrium Biomarketplace hacia plataformas o canales externos, entendiéndose por ello cualquier invitación, mensaje o llamado a la acción orientado a que el usuario concrete la compra fuera de la plataforma.</p>

    <p class="lc-item-num"><span class="lc-num">4.2.5.</span> Dentro del espacio de su tienda en Lyrium Biomarketplace, el Seller podrá incorporar, con fines de identificación de marca, referencias a sus redes sociales, sitio web u otros canales propios, por ejemplo, a través de los banners publicados en su tienda virtual, siempre que dicha mención no constituya una invitación, incentivo o llamado a la acción orientado a que el usuario realice la compra fuera de Lyrium Biomarketplace. En consecuencia, queda prohibido publicar mensajes, medios de contacto alternativos o cualquier contenido que motive, sugiera o induzca directa o indirectamente al usuario a desviar la transacción fuera de la plataforma, así como cualquier contenido que no guarde relación directa con los Productos y/o Servicios registrados y autorizados para su comercialización en Lyrium Biomarketplace.</p>

    <p class="lc-item-num"><span class="lc-num">4.2.6.</span> La veracidad y exactitud de la información publicada en la tienda es responsabilidad íntegra del Seller. Lyrium no asumirá responsabilidad alguna por datos incorrectos ingresados por el Seller, por lo que este deberá revisar de forma permanente todo lo relacionado con sus Productos y/o Servicios, órdenes de compra, stock, precios y demás elementos vinculados a su operación en la plataforma.</p>

    <p class="lc-item-num"><span class="lc-num">4.2.7.</span> Durante toda la vigencia del Acuerdo, Lyrium podrá requerir al Seller, en cualquier momento y sin necesidad de justificación previa, información adicional que estime conveniente en relación con sus Productos y/o Servicios.</p>

    <p class="lc-sub-clausula">4.3 Precio de los Productos y eventos promocionales</p>

    <p class="lc-item-num"><span class="lc-num">4.3.1.</span> El Seller tiene plena libertad para fijar el precio de venta de sus Productos y/o Servicios dentro de su tienda en Lyrium Biomarketplace, debiendo incluir en dicho precio el IGV y los demás impuestos que resulten aplicables.</p>

    <p class="lc-item-num"><span class="lc-num">4.3.2.</span> Los precios de todos los Productos y/o Servicios ofrecidos en la tienda del Seller a través de Lyrium Biomarketplace deberán expresarse en moneda nacional de la República del Perú. Asimismo, el Seller podrá aplicar descuentos y promociones en el momento que considere oportuno, con total autonomía respecto de los Productos y/o Servicios disponibles en su tienda.</p>

    <p class="lc-item-num"><span class="lc-num">4.3.3.</span> Al registrar o editar Productos y/o Servicios, el Seller podrá asignarles una de las siguientes etiquetas promocionales, sujetas a las condiciones que se detallan a continuación:</p>

    <p class="lc-letter-item">a) Descuento: Permite aplicar un porcentaje de reducción no menor al 10% ni mayor al 70% sobre el precio del Producto y/o Servicio. Su vigencia puede ser indefinida, por lo que es decisión asignar o no, una fecha fin.</p>
    <p class="lc-letter-item">b) Oferta: Admite un porcentaje de descuento de hasta el 90%, con una vigencia máxima de 3 (tres) meses. Requiere fecha de inicio y fecha de fin.</p>
    <p class="lc-letter-item">c) Promoción: Requiere la selección de un Producto adicional que será entregado sin costo al Cliente que adquiera la promoción. El Seller podrá fijar libremente el precio de la misma. Esta etiqueta aplica exclusivamente a Productos.</p>

    <p class="lc-p">Las etiquetas Descuento, Oferta y Promoción no pueden coexistir entre sí; un Producto y/o Servicio solo podrá tener asignada una de ellas. Adicionalmente, existen dos etiquetas complementarias:</p>

    <p class="lc-letter-item">a) Nuevo: Aplica a Productos y/o Servicios recién incorporados a la tienda, con una vigencia automática de siete (7) días para visualización del Cliente.</p>
    <p class="lc-letter-item">b) Ed. Limitada: Aplica a Productos y/o Servicios que el Seller designe como edición limitada, debiendo fijar fecha de inicio y fin conforme a su criterio.</p>

    <p class="lc-p">Estas dos etiquetas no pueden coexistir entre sí, pero sí pueden combinarse con cualquiera de las tres etiquetas promocionales anteriores. Para cualquiera de las cinco etiquetas, el Seller podrá incluir de manera opcional una descripción breve que informe al Cliente el motivo de su asignación.</p>

    <p class="lc-item-num"><span class="lc-num">4.3.4.</span> Con el propósito de incentivar la recompra de los Clientes dentro de la plataforma y fortalecer la fidelidad hacia las tiendas de los Sellers, Lyrium ha implementado el programa de fidelización "Lyrios", mediante el cual los Clientes acumulan puntos por sus compras que podrán canjear como descuento en adquisiciones futuras dentro de Lyrium Biomarketplace. Se trata de un beneficio adicional, complementario a las herramientas promocionales previstas en la presente Cláusula, orientado a incrementar el volumen de ventas y la recurrencia de compra de los propios Sellers.</p>

    <p class="lc-letter-item">a) Generación de Lyrios: Por cada compra efectivamente concretada, el Cliente acumulará un Lyrio equivalentes al 1% del Precio Venta del Producto y/o Servicio adquirido, sin incluir el IGV. Dicho porcentaje se calculará de forma independiente por cada Producto y/o Servicio distinto dentro de un mismo pedido, pudiendo acumularse entre unidades de un mismo ítem, mas no entre ítems diferentes. La equivalencia aplicable será de 1 Lyrio = S/ 0.01 (un céntimo de sol).</p>
    <p class="lc-letter-item">b) Uso de Lyrios por el Cliente: El Cliente podrá utilizar sus Lyrios acumulados como descuento en compras futuras dentro de cualquier tienda de la plataforma, sujeto a un monto mínimo de canje equivalente a S/ 2.00 en Lyrios, y a un descuento máximo aplicable por compra equivalente al 3% del precio del Producto y/o Servicio sobre el cual se utilice el beneficio. Estos límites buscan asegurar un uso progresivo y sostenible del programa, sin comprometer de manera significativa el valor comercial de los Productos y/o Servicios ofrecidos por los Sellers.</p>
    <p class="lc-letter-item">c) Asunción del descuento: El descuento que el Cliente aplique mediante el uso de sus Lyrios en una compra será asumido por el Seller titular del Producto y/o Servicio correspondiente, y será deducido del monto que le corresponda percibir conforme al procedimiento de liquidación señalado en el literal j) de la Cláusula Primera. Dicho descuento no afecta en modo alguno la comisión que corresponde a Lyrium, la cual continuará calculándose sobre el Valor Venta original del Producto y/o Servicio, sin considerar el beneficio aplicado por este concepto, conforme a lo dispuesto en la Cláusula Quinta de los presentes Términos y Condiciones Generales para Sellers.</p>

    <p class="lc-p">El Seller declara conocer y aceptar las condiciones de generación, acumulación, visualización y uso de los Lyrios aquí descritas, las cuales podrán ser actualizadas por Lyrium conforme a lo dispuesto en la Cláusula Segunda de los presentes Términos y Condiciones.</p>

    <p class="lc-sub-clausula">4.4 Órdenes de compra y despacho de Productos</p>

    <p class="lc-item-num"><span class="lc-num">4.4.1.</span> Cada vez que un Cliente realice una orden de compra sobre uno o más Productos y/o Servicios publicados en la tienda del Seller dentro de Lyrium Biomarketplace, la plataforma notificará al Seller los datos específicos de la operación, incluyendo tipo de envío, Producto, cantidad, precio pagado y datos del Cliente, para que este inicie el proceso de confirmación correspondiente. De forma simultánea, Lyrium Biomarketplace remitirá al correo electrónico del Cliente la confirmación de pago con el detalle de su orden, permitiéndole realizar el seguimiento respectivo y verificar el cumplimiento de las obligaciones del Seller.</p>

    <p class="lc-item-num"><span class="lc-num">4.4.2.</span> Tras la recepción de una orden de compra, el Seller contará con un plazo máximo de 24 (veinticuatro) horas para:</p>

    <p class="lc-letter-item">a) Confirmar la aceptación de la orden e informar al consumidor en el menor tiempo posible.</p>
    <p class="lc-letter-item">b) Coordinar el despacho de los Productos y/o Servicios al consumidor, asumiendo el compromiso de cumplir con el plazo de entrega informado al momento de la aceptación. La coordinación, despacho y envío desde el punto de origen hasta el domicilio del consumidor es responsabilidad exclusiva del Seller. Lyrium Biomarketplace no asume responsabilidad alguna por estas etapas, sin perjuicio de poner a disposición los medios y herramientas que faciliten dicho proceso entre el Seller y el Cliente.</p>

    <p class="lc-p">Lyrium se reserva la facultad de supervisar que el despacho de Productos y la prestación de Servicios se realicen conforme a los estándares establecidos, sin que ello implique asumir responsabilidad sobre las etapas del proceso de entrega.</p>

    <p class="lc-item-num"><span class="lc-num">4.4.3.</span> Para el despacho de sus Productos, el Seller deberá utilizar medios que garanticen su adecuada protección durante el transporte, tales como cajas, bolsas debidamente acondicionadas u otros elementos de seguridad acordes con la naturaleza del Producto. Con la finalidad de brindar orientación sobre las buenas prácticas de embalaje, Lyrium Biomarketplace pondrá a disposición de sus Sellers un Manual de Empaquetado, el cual contendrá lineamientos generales de empaquetado y tendrá carácter meramente referencial, sin constituir una obligación para el Seller ni sustituir su deber de adoptar las medidas de embalaje que resulten adecuadas para cada Producto. En consecuencia, cualquier reclamo, queja o contingencia relacionada con el estado del empaquetamiento o embalaje será de exclusiva responsabilidad del Seller, quien se obliga a entregar los Productos en condiciones óptimas y a prestar Servicios de calidad.</p>

    <p class="lc-item-num"><span class="lc-num">4.4.4.</span> El Seller está obligado a realizar la entrega de los Productos y/o Servicios dentro del plazo establecido y comunicado a través de la plataforma Lyrium Biomarketplace, sin excepciones ni demoras injustificadas.</p>

    <p class="lc-item-num"><span class="lc-num">4.4.5.</span> El Seller deberá monitorear de forma continua el estado del despacho de sus Productos hasta su entrega completa en el domicilio correspondiente, manteniendo informados a los consumidores que así lo soliciten, ya sea a través de la plataforma o de los canales habilitados, incluidos los módulos Chat con Clientes o en el caso del Cliente, Chat con Vendedores. La comunicación entre el Seller y el consumidor durante este proceso deberá limitarse estrictamente a informar sobre el estado del despacho, la entrega del Producto y/o la coordinación del Servicio.</p>

    <p class="lc-p">Sin perjuicio de ello, Lyrium podrá supervisar el proceso de coordinación y despacho a través de la plataforma, verificando que la información se mantenga actualizada para conocimiento de los consumidores. Los datos recabados durante este seguimiento serán utilizados por Lyrium para evaluar el cumplimiento del Seller respecto de las obligaciones establecidas en los presentes Términos y Condiciones y en el Acuerdo, y para determinar las penalidades que pudieran corresponder.</p>

    <p class="lc-item-num"><span class="lc-num">4.4.6.</span> Para la entrega de Productos, la plataforma contempla tres modalidades de envío: Entrega a Domicilio, Recojo en Agencia (agencia del operador logístico) y Recojo en Tienda (sucursal escogida por el Cliente para poder asistir a ella). Cada modalidad sigue una secuencia de estados específica:</p>

    <p class="lc-letter-item">a) Entrega a Domicilio: Validado por el Seller → En Preparación → Despachado → En camino → Listo en Domicilio → Confirmado por el Cliente.</p>
    <p class="lc-letter-item">b) Recojo en Agencia: Validado por el Seller → En Preparación → Despachado → En camino → Listo para Recojo en Agencia → Confirmado por el Cliente.</p>
    <p class="lc-letter-item">c) Recojo en Tienda: Validado por el Seller → En Preparación → Despachado → Listo para Recojo en Tienda → Confirmado por el Cliente.</p>

    <p class="lc-p">Para la prestación de Servicios, la modalidad de atención disponible es solo Atención en Sede o Centro de Salud con el siguiente estado:</p>

    <p class="lc-letter-item">a) Atención en Centro de Salud: Validado por el Centro de Salud → Confirmado por el Paciente.</p>

    <p class="lc-p">En todos los casos, el Cliente podrá elegir libremente la modalidad que mejor se adapte a sus necesidades, y el Seller queda obligado a ejecutar el proceso seleccionado, respetando en todo momento la secuencia de estados que le corresponda.</p>

    <p class="lc-item-num"><span class="lc-num">4.4.7.</span> El Seller tiene la obligación de emitir oportunamente el comprobante de pago correspondiente por la venta de los Productos y/o la prestación de los Servicios, así como de remitirlo al Cliente final de manera oportuna, ya sea de forma presencial al momento de la entrega o mediante medios virtuales idóneos, tales como correo electrónico, WhatsApp u otros que permitan su recepción por el Cliente. El comprobante de pago deberá ser emitido bajo el nombre, razón o denominación social y número de RUC del Seller, junto con cualquier otro documento tributario exigible conforme a la normativa vigente. Asimismo, dicho comprobante deberá reflejar el monto total abonado por el Cliente, comprendiendo el precio del Producto y/o Servicio, el costo de despacho, los impuestos aplicables y la modalidad de envío o entrega seleccionada al momento de la orden de compra, así como todos los datos relativos a la adquisición y cualquier otra información de carácter obligatorio de conformidad con la legislación aplicable.</p>

    <p class="lc-item-num"><span class="lc-num">4.4.8.</span> El Seller reconoce y acepta que, para cada Producto y/o Servicio publicado en su tienda dentro de Lyrium Biomarketplace, el Cliente tendrá la facultad de elegir libremente la modalidad de envío o atención que estime conveniente entre las opciones habilitadas por Lyrium, siendo dicha elección de cumplimiento obligatorio para el Seller.</p>

    <p class="lc-p">Para Productos, las modalidades disponibles son envío a domicilio, recojo en agencia y recojo en tienda; para Servicios, atención en sede o centro de salud. La correcta ejecución de cada modalidad —incluyendo la coordinación, actualización de estados, cumplimiento de plazos, generación de evidencias y comunicación al Cliente sobre el estado del pedido o la programación del Servicio— es responsabilidad exclusiva del Seller.</p>

    <p class="lc-p">Lyrium únicamente provee la herramienta tecnológica que permite el registro, visualización y seguimiento del flujo correspondiente, sin asumir responsabilidad alguna sobre la logística, transporte, atención, reprogramación, entrega, cancelación, reembolso ni cualquier otro acto de ejecución material o postventa, salvo que se disponga expresamente lo contrario en los presentes Términos y Condiciones.</p>

    <p class="lc-item-num"><span class="lc-num">4.4.9.</span> Lyrium Biomarketplace pone a disposición de todos los Sellers, sin costo adicional y sin que ello forme parte de la comisión establecida en la Cláusula Quinta ni de los beneficios asociados a los planes de afiliación, una herramienta que calcula automáticamente el costo de envío de cada pedido al momento en que el Cliente realiza su compra. Dicho cálculo se determina en función del operador logístico seleccionado, el peso y las dimensiones del paquete, y demás variables propias de la operación de despacho, conforme a las reglas y tarifas establecidas por cada operador logístico.</p>

    <p class="lc-p">El monto resultante de este cálculo corresponde íntegramente al Seller, como parte del costo operativo de despacho de sus Productos, y será considerado dentro de la liquidación semanal señalada en el literal j) de la Cláusula Primera.</p>

    <p class="lc-p">El Seller reconoce que, por la naturaleza del cálculo, el cual se basa en las especificaciones técnicas registradas para cada Producto y en las tarifas del operador logístico correspondiente, el monto proyectado puede presentar un margen de variación razonable frente al costo real que finalmente aplique el operador logístico al momento del despacho efectivo. Sin perjuicio de ello, el monto reflejado al Cliente al momento de la compra será el que corresponda cobrar por dicha operación, resultando responsabilidad del Seller evaluar y gestionar cualquier diferencia que pudiera surgir frente al operador logístico que utilice.</p>

    <p class="lc-p">Cualquier situación relacionada con el costo de envío de un pedido en particular podrá ser canalizada entre el Seller y el Cliente a través de los módulos "Chat con Clientes" y "Chat con Vendedores" habilitados en la plataforma, conforme a lo previsto en la Cláusula Sexta de los presentes Términos y Condiciones.</p>

    <p class="lc-sub-clausula">4.5 Recaudación de pagos</p>

    <p class="lc-item-num"><span class="lc-num">4.5.1.</span> Lyrium se encargará de recaudar los pagos realizados por los Clientes con ocasión de sus órdenes de compra, los cuales comprenden el precio de los Productos y/o Servicios adquiridos, el costo de despacho y los impuestos que resulten aplicables.</p>

    <p class="lc-item-num"><span class="lc-num">4.5.2.</span> Todo impuesto, FEE, retención u otro gravamen que se genere como consecuencia de las operaciones comerciales del Seller será asumido íntegra y exclusivamente por este, sin que Lyrium tenga responsabilidad alguna al respecto.</p>

    {{-- ════════════════════════ 5. QUINTO ════════════════════════ --}}
    <p class="lc-clausula">Quinto: Comisión</p>

    <p class="lc-item-num"><span class="lc-num">5.1.</span> Como retribución por los servicios que Lyrium presta al Seller dentro de la plataforma Lyrium Biomarketplace, este último abonará la comisión que corresponda según la tabla de FEE vigente, calculada sobre el Valor Venta de cada Producto y/o Servicio efectivamente vendido, sin considerar el IGV.</p>

    <p class="lc-item-num"><span class="lc-num">5.2.</span> Para determinar la FEE de comisión aplicable a cada operación, se tomará como base el valor total de la transacción individual de cada Producto y/o Servicio comercializado, entendido como la suma del precio de todas las unidades de dicho ítem adquiridas en una misma compra, excluido el IGV. El cálculo se realizará de forma independiente para cada Producto y/o Servicio vendido por cada Seller, por lo que los montos correspondientes a ítems distintos o pertenecientes a otros Sellers dentro de una misma transacción no podrán acumularse ni tomarse en cuenta para definir la categoría de comisión aplicable.</p>

    <p class="lc-p">A continuación, se aplicarán las siguientes FEE de comisión:</p>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Venta mínima</th>
                    <th>Venta máxima</th>
                    <th>Tasa de comisión de Lyrium</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>1</td><td>S/ 0.00</td><td>S/ 400.00</td><td>15%</td></tr>
                <tr><td>2</td><td>S/ 401.00</td><td>S/ 800.00</td><td>14%</td></tr>
                <tr><td>3</td><td>S/ 801.00</td><td>S/ 1,200.00</td><td>13%</td></tr>
                <tr><td>4</td><td>S/ 1,201.00</td><td>A más</td><td>12%</td></tr>
            </tbody>
        </table>
    </div>

    <p class="lc-item-num"><span class="lc-num">5.3.</span> Para los fines del presente Acuerdo, se entiende por Valor Venta el precio del Producto y/o Servicio antes de la aplicación del IGV o de cualquier otro tributo que pudiera corresponder.</p>

    <p class="lc-item-num"><span class="lc-num">5.4.</span> Lyrium podrá revisar y actualizar la tabla de comisiones cuando lo estime pertinente. Toda modificación deberá ser comunicada al Seller a través de los canales oficiales habilitados en la plataforma Lyrium Biomarketplace, entre ellos, el número oficial de WhatsApp, el Asistente Virtual/ChatBot, el correo electrónico y el Módulo de Soporte Lyrium, y tendrá efecto únicamente sobre las ventas realizadas con posterioridad a dicha comunicación.</p>

    <p class="lc-item-num"><span class="lc-num">5.5.</span> En caso de discrepancia entre lo dispuesto en los presentes Términos y Condiciones y la tabla de comisiones disponible a través del Asistente Virtual/ChatBot, prevalecerá esta última en su versión vigente y comunicada oficialmente por Lyrium.</p>

    <p class="lc-item-num"><span class="lc-num">5.6.</span> Por cada venta efectiva realizada a través de la tienda del Seller en Lyrium Biomarketplace, Lyrium emitirá el comprobante de pago correspondiente, ya sea boleta o factura, en el que se detallará la comisión cobrada por concepto de uso de la plataforma. Dicho comprobante será remitido de forma automática al correo electrónico que el Seller tenga registrado en su cuenta.</p>

    {{-- ════════════════════════ 6. SEXTO ════════════════════════ --}}
    <p class="lc-clausula">Sexto: Cancelaciones, Devoluciones, Cambio, Garantía, Reembolsos y Reprogramaciones</p>

    <p class="lc-item-num"><span class="lc-num">6.1.</span> Cada Seller es responsable de establecer y gestionar sus propias políticas en materia de cancelaciones, devoluciones, cambios, garantías, reembolsos y reprogramaciones. Lyrium Biomarketplace no asume responsabilidad alguna frente al Cliente cuando este opte por ejercer cualquiera de estas opciones.</p>

    <p class="lc-item-num"><span class="lc-num">6.2.</span> Dentro de su tienda en Lyrium Biomarketplace, el Seller deberá publicar de manera visible y comprensible las políticas aplicables a cada uno de los Productos y/o Servicios que ofrezca, incluyendo las condiciones de cancelación, devolución, cambio, garantía, reembolso y reprogramación correspondientes.</p>

    <p class="lc-item-num"><span class="lc-num">6.3.</span> La definición, aplicación y cumplimiento de las políticas de cancelaciones, devoluciones, cambios, garantías, reembolsos y reprogramaciones recae de manera exclusiva sobre el Seller. Lyrium no interviene en dichos procesos ni asume responsabilidad por su ejecución o inobservancia respecto de los Productos y/o Servicios comercializados a través de la plataforma.</p>

    <p class="lc-item-num"><span class="lc-num">6.4.</span> La prestación del servicio técnico asociado a los Productos y/o Servicios adquiridos por los Clientes es una obligación que corresponde única y exclusivamente al Seller, quien deberá atenderla siempre que el consumidor lo solicite y la naturaleza del Producto y/o Servicio lo justifique.</p>

    <p class="lc-item-num"><span class="lc-num">6.5.</span> Toda solicitud de cancelación, devolución, cambio, reembolso o reprogramación deberá ser tramitada directamente entre el consumidor y el Seller. Lyrium no interviene ni es parte necesaria en dicha comunicación.</p>

    <p class="lc-item-num"><span class="lc-num">6.6.</span> Cuando un consumidor manifieste su intención de ejercer cualquiera de los derechos contemplados en el presente apartado, el Seller deberá contactarlo a través de los canales oficiales disponibles en la plataforma, informándole sobre la procedencia de su solicitud, así como las condiciones, restricciones y plazos bajo los cuales operará el proceso correspondiente.</p>

    <p class="lc-p">Para facilitar esta gestión, Lyrium Biomarketplace pone a disposición del Cliente, dentro de su panel personal, el módulo "Chat con Vendedores", a través del cual podrá coordinar directamente con el Seller todo lo relacionado con los procesos descritos en este apartado.</p>

    {{-- ════════════════════════ 7. SÉPTIMO ════════════════════════ --}}
    <p class="lc-clausula">Séptimo: Propiedad Intelectual</p>

    <p class="lc-item-num"><span class="lc-num">7.1.</span> Al publicitar y/o comercializar Productos y/o Servicios a través de su tienda en Lyrium Biomarketplace, el Seller garantiza que estos han sido adquiridos mediante actividades lícitas y que su ingreso al territorio peruano y al mercado local se ha producido conforme a la legislación vigente. Del mismo modo, el Seller reconoce ser el único responsable de las imágenes, marcas, descripciones y demás elementos asociados a los Productos y/o Servicios que publique o comercialice dentro de la plataforma, y declara que estos fueron obtenidos tras su comercialización legítima, ya sea en el Perú o en cualquier otro país por parte de los titulares de las marcas correspondientes, o con su consentimiento expreso.</p>

    <p class="lc-p">El Seller tiene la obligación de contar con la totalidad de los derechos de propiedad intelectual e industrial sobre los signos bajo los cuales publicite y/o comercialice sus Productos y/o Servicios en la plataforma. En virtud de ello, se compromete a mantener a Lyrium completamente a salvo de cualquier responsabilidad civil, penal, administrativa o de otra índole que pudiera derivarse de reclamaciones, demandas o acciones vinculadas al uso de signos distintivos o de cualquier otro elemento protegido por la propiedad intelectual e industrial, ya sea que estas provengan de los titulares de dichos derechos o de terceros. Esta obligación de indemnidad se extiende a cualquier tipo de reclamación judicial o administrativa que terceros pudieran interponer en relación con los Productos y/o Servicios comercializados a través de la tienda del Seller en Lyrium Biomarketplace, incluso en los casos en que dichas reclamaciones sean acogidas parcial o totalmente. Su vigencia se mantendrá aun después de la terminación del Acuerdo, durante todos los plazos de prescripción que resulten aplicables.</p>

    <p class="lc-p">Cuando Lyrium lo requiera, el Seller deberá entregar de forma inmediata la documentación que acredite, a satisfacción de Lyrium, la legalidad de la mercancía, su procedencia, las condiciones de su importación o adquisición, su permanencia legal en el país, así como la titularidad plena de los derechos de propiedad intelectual e industrial sobre los signos distintivos bajo los cuales se publiciten y/o comercialicen los Productos. Dicha documentación podrá incluir, según el tipo de producto y la situación particular, facturas de compra, certificados de origen, declaraciones o pólizas de importación, registros sanitarios, certificados de marca o cualquier otro soporte que permita a Lyrium verificar el cumplimiento de lo aquí establecido.</p>

    <p class="lc-item-num"><span class="lc-num">7.2.</span> Bajo ninguna circunstancia el Seller podrá publicar ni comercializar, a través de su tienda en Lyrium Biomarketplace, productos falsificados, replicados, copiados o adulterados de cualquier manera que pudiera inducir a error al consumidor o generar la apariencia de autenticidad u originalidad. Igualmente, queda prohibida la publicación o comercialización de productos cuyos signos distintivos, imágenes u otros elementos protegidos por la propiedad intelectual y/o industrial hayan sido falsificados, copiados o constituyan una reproducción exacta o sustancialmente similar a los de otro producto o proveedor del mercado, o que de cualquier forma imiten, reproduzcan o se aprovechen indebidamente de dichos elementos.</p>

    {{-- ════════════════════════ 8. OCTAVO ════════════════════════ --}}
    <p class="lc-clausula">Octavo: Resolución del Acuerdo y Eliminación de Cuenta del Seller</p>

    <p class="lc-item-num"><span class="lc-num">8.1.</span> Vencido el período de gracia único de 6 (seis) meses a que se refiere el literal l) de la Cláusula Primera, y de no haber alcanzado la venta mínima exigida por Lyrium, el Acuerdo quedará resuelto de pleno derecho, lo que conllevará la eliminación inmediata de su cuenta y tienda de la plataforma, salvo que el Seller haya renovado el Acuerdo dentro de los plazos y condiciones establecidos por Lyrium para tal efecto, en cuyo caso el Acuerdo continuará vigente conforme a las nuevas condiciones pactadas, sin que ello implique el otorgamiento de un nuevo período de gracia.</p>

    <p class="lc-item-num"><span class="lc-num">8.2.</span> Brindar a los consumidores una experiencia de compra de calidad es un objetivo compartido por ambas partes. En virtud de ello, el Seller se compromete a mantener, durante toda la vigencia del Acuerdo, estándares óptimos en cada etapa de la relación comercial con sus clientes: desde la oferta y comercialización de sus Productos y/o Servicios a través de su tienda en Lyrium Biomarketplace, hasta el despacho y el servicio postventa, abarcando cuando corresponda los procesos de cancelación, devolución, cambio, garantía, reembolso y reprogramación.</p>

    <p class="lc-item-num"><span class="lc-num">8.3.</span> Lyrium se reserva el derecho de resolver unilateralmente el Acuerdo, de pleno derecho, y de proceder a la eliminación definitiva de la cuenta y tienda del Seller dentro de la plataforma Lyrium Biomarketplace, cuando se verifique cualquiera de las siguientes situaciones:</p>

    <p class="lc-roman-item">(i) Se constaten, en hasta 3 (tres), deficiencias significativas en el servicio prestado a los Clientes, acreditadas mediante calificaciones negativas, reclamos fundados u otros medios objetivos de verificación debidamente sustentadas por Lyrium ante el Seller.</p>
    <p class="lc-roman-item">(ii) El Seller incurra en el incumplimiento de cualquier obligación contemplada en el presente Acuerdo o en los presentes Términos y Condiciones.</p>
    <p class="lc-roman-item">(iii) El Seller no logre sostener la venta mínima mensual exigida por Lyrium una vez transcurrido el período de gracia único de 6 (seis) meses a que se refiere el literal l) de la Cláusula Primera, esto con el fin de la permanencia activa en la plataforma, equivalente a S/ 350.00 (trescientos cincuenta soles) mensuales en comercialización de Productos, y a S/ 450.00 (cuatrocientos cincuenta soles) mensuales en prestación de Servicios.</p>

    <p class="lc-p">Ambas partes reconocen expresamente que una suspensión previa de la cuenta del Seller no limita ni condiciona la facultad de Lyrium de resolver el Acuerdo en una instancia posterior.</p>

    <p class="lc-item-num"><span class="lc-num">8.4.</span> Cuando el Seller acumule hasta 3 (tres) incumplimientos de las obligaciones contenidas en los presentes Términos y Condiciones y/o en el Acuerdo, Lyrium quedará facultada para dar por resuelto el vínculo contractual y proceder a la eliminación de la cuenta y tienda del Seller de la plataforma. Entre los supuestos que configuran dicho incumplimiento se encuentran, sin carácter limitativo: la oferta o comercialización de Productos y/o Servicios distintos a los acordados con Lyrium; la entrega incompleta, tardía o defectuosa de los Productos y/o Servicios adquiridos por los consumidores a través de su tienda en Lyrium Biomarketplace; y el incumplimiento de las políticas de cancelación, devolución, cambio, garantía, reembolso o reprogramación.</p>

    <p class="lc-item-num"><span class="lc-num">8.5.</span> El incumplimiento de las responsabilidades asumidas por cualquiera de las partes constituye causal de resolución del presente Acuerdo, al amparo de lo dispuesto en el artículo 1430 del Código Civil. La resolución operará de pleno derecho desde el momento en que la parte que invoca esta cláusula notifique a la otra mediante comunicación dirigida a su dirección de correo electrónico registrada.</p>

    {{-- ════════════════════════ 9. NOVENO ════════════════════════ --}}
    <p class="lc-clausula">Noveno: Responsabilidad</p>

    <p class="lc-item-num"><span class="lc-num">9.1.</span> La oferta y comercialización de Productos y/o Servicios dentro de Lyrium Biomarketplace se desarrolla como una relación directa entre el Seller y los Clientes. La participación de Lyrium en dicho proceso se circunscribe exclusivamente a: (i) facilitar la publicación y visibilidad de la información relativa a los Productos y/o Servicios del Seller dentro su tienda mediante Lyrium Biomarketplace; (ii) gestionar el cobro de los pagos generados con ocasión de dichas transacciones; y (iii) cumplir con las demás obligaciones que le han sido expresamente asignadas en los presentes Términos y Condiciones y en el Acuerdo.</p>

    <p class="lc-item-num"><span class="lc-num">9.2.</span> A través de Lyrium Biomarketplace, Lyrium pone a disposición del Seller un entorno virtual materializado como su propia tienda dentro de la plataforma que le permite conectar con distintos Clientes para ofrecer y comercializar sus Productos y/o Servicios. No obstante, todo lo relacionado con las condiciones bajo las cuales se realiza dicha oferta y comercialización, así como cualquier aspecto vinculado a los Productos y/o Servicios mismos, es de entera y exclusiva responsabilidad del Seller.</p>

    <p class="lc-item-num"><span class="lc-num">9.3.</span> Lyrium no ostenta titularidad, posesión ni poder de disposición sobre los Productos y/o Servicios que los Sellers comercializan a través de sus tiendas en la plataforma Lyrium Biomarketplace. Su rol se limita al de facilitador del entorno digital, sin intervenir en la naturaleza ni en las condiciones de lo que se ofrece.</p>

    <p class="lc-item-num"><span class="lc-num">9.4.</span> Lyrium no toma parte en la concreción de las transacciones celebradas entre el Seller y los Clientes a través de las tiendas dentro de Lyrium Biomarketplace, ni en las condiciones bajo las cuales estas se perfeccionan y ejecutan. En tal sentido, constituyen aspectos propios del perfeccionamiento y ejecución del pedido, sin carácter limitativo, la aceptación, preparación, despacho, entrega, cancelación, reprogramación, devolución, cambio, reposición, reembolso, atención de garantías, servicio postventa y cualquier otra gestión relacionada con el cumplimiento de la compraventa de Productos o de la prestación de Servicios, incluyendo las decisiones vinculadas a las citas agendadas para dichos fines.</p>

    <p class="lc-p">Por tanto, el Seller asume de forma exclusiva la responsabilidad por la comercialización de sus Productos y/o Servicios a través de su tienda en la plataforma, así como por el cumplimiento íntegro de todas las obligaciones legales, regulatorias y contractuales que le correspondan como proveedor en la relación de consumo que se genere con el Cliente.</p>

    <p class="lc-p">Bajo este marco, Lyrium queda eximida de toda responsabilidad respecto de la existencia, disponibilidad, calidad, idoneidad, estado, cantidad, legitimidad o cualquier otra característica de los Productos y/o Servicios ofrecidos por el Seller. Del mismo modo, Lyrium no asumirá responsabilidad alguna por incidencias relacionadas con el perfeccionamiento o la ejecución del pedido, incluyendo, entre otras, demoras, errores, pérdidas, entregas en un lugar distinto al acordado, cancelaciones, devoluciones, cambios, reposiciones, reembolsos, atención de garantías, servicio postventa o cualquier incumplimiento atribuible al Seller.</p>

    <p class="lc-item-num"><span class="lc-num">9.5.</span> Queda terminantemente prohibido que el Seller haga uso de las marcas "Lyrium" o "Lyrium Biomarketplace", así como de cualquier otro signo distintivo, elemento registrado o activo protegido por la propiedad industrial e intelectual de titularidad de Lyrium o de sus empresas vinculadas. De igual forma, el Seller deberá abstenerse de distribuir, por cualquier canal o medio, publicidad, cupones, documentos o cualquier tipo de contenido que pueda ser asociado o confundido con dichos signos, marcas o elementos.</p>

    {{-- ════════════════════════ 10. DÉCIMO ════════════════════════ --}}
    <p class="lc-clausula">Décimo: Vigencia</p>

    <p class="lc-item-num"><span class="lc-num">10.1.</span> La adhesión a los presentes Términos y Condiciones Generales para Sellers se producirá con la firma del Acuerdo, momento desde el cual resultarán plenamente aplicables y de obligatorio cumplimiento durante todo el tiempo de su vigencia. A partir de dicha suscripción, el Seller quedará sometido a lo dispuesto en este marco contractual. El plazo de duración del Acuerdo será el que corresponda al plan contratado o adquirido por el Seller, conforme a lo dispuesto en el literal l) de la Cláusula Primera.</p>

    <p class="lc-item-num"><span class="lc-num">10.2.</span> Producida la terminación del Acuerdo, cualquiera sea su causa, Lyrium quedará facultada para desactivar al Seller dentro de la plataforma Lyrium Biomarketplace y retirar su tienda virtual junto con la totalidad de los Productos y/o Servicios que hubieren sido publicados por este. Asimismo, Lyrium efectuará la entrega de los importes que hubiera recaudado con ocasión de la prestación de los Servicios, siempre que el Seller no haya procedido a la renovación del Acuerdo.</p>

    <p class="lc-item-num"><span class="lc-num">10.3.</span> La finalización del Acuerdo, sin importar el motivo que la origine, no extinguirá aquellas obligaciones que, por su naturaleza o por mandato legal, deban subsistir con posterioridad a su vencimiento. En particular, permanecerán exigibles durante el plazo de prescripción aplicable las obligaciones del Seller frente a Lyrium, así como las disposiciones de estos Términos y Condiciones Generales para Sellers y del Acuerdo vinculadas con el despacho, cambio, devolución, reposición, servicio técnico y garantía de los Productos adquiridos por los consumidores con anterioridad a la terminación del Acuerdo.</p>

    <p class="lc-item-num"><span class="lc-num">10.4.</span> Con una antelación de 7 (siete) días calendario al vencimiento del Acuerdo y del plan contratado o adquirido por el Seller, Lyrium remitirá a este una comunicación vía correo electrónico informándole dicho vencimiento. La renovación del Acuerdo y, por ende, de los presentes Términos y Condiciones Generales para Sellers, podrá efectuarse de dos formas: (i) de manera manual, ingresando el Seller a su panel de vendedor dentro de la plataforma Lyrium Biomarketplace, módulo "Mi Plan", y haciendo uso de la función de renovación habilitada para tal efecto; o (ii) de manera automática, siempre que el Seller haya activado previamente la opción de renovación automática disponible en el mismo módulo. El Seller podrá activar o desactivar dicha opción de renovación automática en cualquier momento, a través de su panel de vendedor. En caso el Seller no cuente con la renovación automática activada y no ejecute la renovación de forma manual, el Acuerdo quedará resuelto de pleno derecho al vencimiento del plazo correspondiente, sin perjuicio de lo señalado en la Cláusula Octava.</p>

    {{-- ════════════════════════ 11. UNDÉCIMO ════════════════════════ --}}
    <p class="lc-clausula">Undécimo: Comunicaciones</p>

    <p class="lc-item-num"><span class="lc-num">11.1.</span> Ambas partes designarán a uno o más administradores del Acuerdo, quienes, por el solo hecho de su nombramiento, quedarán facultados para representarla en todo lo concerniente a la administración y ejecución de los presentes Términos y Condiciones y del Acuerdo respectivo. Cada parte definirá internamente a sus administradores y podrá reemplazarlos cuando lo considere conveniente, debiendo notificar a la otra parte el nombre del nuevo administrador por cualquier medio escrito.</p>

    <p class="lc-item-num"><span class="lc-num">11.2.</span> Los datos de contacto de cada administrador del Acuerdo —incluyendo nombre completo, número de teléfono, correo electrónico y cualquier otra información relevante— serán registrados directamente en el Acuerdo suscrito entre las partes.</p>

    <p class="lc-item-num"><span class="lc-num">11.3.</span> Toda comunicación que deba realizarse en el marco de los presentes Términos y Condiciones y/o del Acuerdo deberá dirigirse al administrador designado por la parte correspondiente, a través de los medios de contacto registrados para tal fin.</p>

    {{-- ════════════════════════ 12. DUODÉCIMO ════════════════════════ --}}
    <p class="lc-clausula">Duodécimo: Cesión del Contrato y los Términos</p>

    <p class="lc-item-num"><span class="lc-num">12.1.</span> La transferencia del Acuerdo, de los presentes Términos y Condiciones, o de cualquier derecho u obligación que se desprenda de ellos, requerirá en todos los casos la autorización previa, expresa y por escrito de la parte contraria. Ninguna cesión será válida si no cuenta con dicho consentimiento formal.</p>

    <p class="lc-item-num"><span class="lc-num">12.2.</span> No obstante lo anterior, Lyrium tendrá la potestad de transferir el Acuerdo y los presentes Términos y Condiciones —incluyendo los derechos y obligaciones vinculados a estos— a favor de cualquiera de los socios que integren su estructura empresarial, sin que ello requiera aprobación adicional por parte del Seller.</p>

    {{-- ════════════════════════ 13. DÉCIMOTERCERA ════════════════════════ --}}
    <p class="lc-clausula">Décimotercera: Protección y Tratamiento de Datos Personales</p>

    <p class="lc-item-num"><span class="lc-num">13.1.</span> Con ocasión de la ejecución del Acuerdo y de los presentes Términos y Condiciones Generales para Sellers, las Partes podrán acceder, compartir, transferir y, en términos generales, tratar datos personales correspondientes tanto a los Sellers como a sus clientes y/o consumidores.</p>

    <p class="lc-item-num"><span class="lc-num">13.2.</span> En atención a ello, las Partes se obligan a que todo tratamiento de datos personales efectuado en el marco del Acuerdo y de estos Términos y Condiciones Generales para Sellers se realice bajo estrictos deberes de confidencialidad y conforme a las disposiciones contractuales aquí previstas, así como a lo establecido en la Ley N.° 29733, Ley de Protección de Datos Personales, su Reglamento aprobado por Decreto Supremo N.° 003-2013-JUS, y las normas complementarias, modificatorias, sustitutorias y demás disposiciones que resulten aplicables en la materia.</p>

    <p class="lc-item-num"><span class="lc-num">13.3.</span> Como parte de la ejecución del Acuerdo, Lyrium recopila y trata los datos personales que el Seller proporciona durante su registro y a lo largo de la relación comercial, incluyendo, entre otros, RUC, nombre comercial o razón social, domicilio legal, datos de contacto del Administrador del Acuerdo, información bancaria para el pago de sus ventas y demás datos consignados en el formulario de registro y en el Acuerdo, con las siguientes finalidades:</p>

    <p class="lc-letter-item">a.) Gestionar el registro del Seller y la activación de su cuenta y tienda dentro de la plataforma.</p>
    <p class="lc-letter-item">b.) Verificar la identidad del Seller y validar la información proporcionada durante el proceso de registro, incluyendo la vinculación de su actividad comercial con los rubros de bienestar, salud o cuidado personal.</p>
    <p class="lc-letter-item">c.) Gestionar la relación comercial entre el Seller y Lyrium, incluyendo la administración del Acuerdo y el cumplimiento de las obligaciones asumidas por ambas partes.</p>
    <p class="lc-letter-item">d.) Procesar la recaudación y liquidación de los pagos correspondientes a las ventas del Seller, así como la emisión de los comprobantes de pago respectivos.</p>
    <p class="lc-letter-item">e.) Atender consultas, solicitudes, reclamos y cualquier otra comunicación cursada por el Seller, brindando el seguimiento correspondiente hasta su atención.</p>
    <p class="lc-letter-item">f.) Enviar comunicaciones relacionadas con el funcionamiento de la plataforma, tales como notificaciones, alertas, actualizaciones de seguridad y cambios en los Servicios.</p>
    <p class="lc-letter-item">g.) Elaborar estudios, reportes e indicadores comerciales o de desempeño, utilizando información agregada o tratada conforme a la normativa vigente.</p>
    <p class="lc-letter-item">h.) Cumplir con obligaciones legales, regulatorias, administrativas y tributarias aplicables a Lyrium.</p>
    <p class="lc-letter-item">i.) Detectar, prevenir e investigar posibles actividades fraudulentas, accesos no autorizados o incumplimientos del Acuerdo y de los presentes Términos y Condiciones.</p>

    <p class="lc-item-num"><span class="lc-num">13.4.</span> En particular, y a título enunciativo mas no limitativo, las Partes declaran que:</p>

    <p class="lc-letter-item">a) Los titulares de los datos personales involucrados en el tratamiento han sido informados previamente, de forma clara, expresa, completa e inequívoca, respecto de la finalidad de la recopilación de sus datos, el tipo de tratamiento a realizarse, la forma en que este será llevado a cabo, los responsables intervinientes y, en general, las medidas técnicas, organizativas y legales adoptadas para resguardar la confidencialidad y seguridad de la información.</p>
    <p class="lc-letter-item">b) Se ha obtenido de los titulares la autorización y el consentimiento libre, previo, informado, expreso e inequívoco para el tratamiento de sus datos personales.</p>
    <p class="lc-letter-item">c) Los titulares han sido informados previamente, de forma clara, expresa, detallada e inequívoca, sobre el alcance de los derechos que les reconoce la normativa aplicable, incluyendo los derechos de información, acceso, actualización, inclusión, rectificación, supresión, impedimento de suministro, oposición, tratamiento objetivo, tutela, indemnización y los demás que correspondan.</p>
    <p class="lc-letter-item">d) Asimismo, los titulares han recibido información previa, clara, expresa, detallada e inequívoca acerca de los mecanismos, canales y/o medios habilitados para ejercer los derechos mencionados en el literal anterior.</p>

    {{-- ════════════════════════ 14. DÉCIMOCUARTA ════════════════════════ --}}
    <p class="lc-clausula">Décimocuarta: Confidencialidad</p>

    <p class="lc-item-num"><span class="lc-num">14.1.</span> Desde el momento en que el Seller se vincula a Lyrium Biomarketplace, asume de manera plena y consciente el compromiso de preservar en absoluta reserva toda información a la que tenga acceso, ya sea de forma directa o indirecta, y que guarde relación con los negocios, operaciones, actividades o intereses de Lyrium. Este deber se extiende más allá de la vigencia del Acuerdo y de los presentes Términos y Condiciones, manteniéndose vigente incluso tras su terminación. Dentro del alcance de esta obligación se encuentran, entre otros, los siguientes elementos: condiciones comerciales, relaciones de negocio, contratos, tecnología, proyectos en desarrollo, especificaciones de productos, plataformas digitales, procesos operativos, diseños, patentes, fórmulas, secretos industriales, know-how, ideas comerciales o industriales, información técnica y, en términos generales, cualquier dato o antecedente que, por su naturaleza, sea propio de Lyrium o respecto del cual sea razonable presumir que Lyrium no tiene interés en su divulgación a terceros no autorizados.</p>

    <p class="lc-item-num"><span class="lc-num">14.2.</span> El acceso a la información confidencial de Lyrium se limita exclusivamente al período de vigencia del Acuerdo y únicamente en la medida estrictamente necesaria para que el Seller pueda cumplir con las obligaciones pactadas. Fuera de este propósito, queda prohibido cualquier otro uso. Asimismo, ante la existencia de un conflicto de interés actual o potencial relacionado con dicha información y derivado de actividades que el Seller realice o pudiera realizar para terceros, este deberá comunicarlo a Lyrium de forma inmediata.</p>

    <p class="lc-item-num"><span class="lc-num">14.3.</span> La obligación de confidencialidad aquí establecida tiene carácter permanente e indefinido. Su vigencia no se encuentra condicionada a la duración del Acuerdo ni de los presentes Términos y Condiciones, y subsistirá de manera autónoma con independencia del momento en que se produzca su terminación.</p>

    <p class="lc-item-num"><span class="lc-num">14.4.</span> Fuera de los supuestos expresamente permitidos en los presentes Términos y Condiciones, el Seller no podrá mantener comunicación directa con los clientes de la plataforma, ni llevar a cabo acciones de propaganda, publicidad, ofertas u otras iniciativas comerciales no autorizadas por Lyrium, incluyendo aquellas que pudieran realizarse a través de los envíos o pedidos gestionados por Lyrium Biomarketplace.</p>

    <p class="lc-item-num"><span class="lc-num">14.5.</span> Una vez concluida la relación contractual, el Seller quedará obligado a restituir de forma inmediata e integral todos los documentos, archivos y demás información material que obre en su poder y que sea de propiedad de Lyrium o esté vinculada a sus actividades o intereses. Dicha restitución deberá efectuarse en un solo acto, sin dilaciones ni retención parcial de ningún tipo.</p>

    <p class="lc-item-num"><span class="lc-num">14.6.</span> El alcance de la presente cláusula se extiende a los trabajadores, empresas relacionadas, representantes y socios del Seller. En consecuencia, este deberá adoptar todas las medidas internas que sean necesarias para garantizar que dichas personas conozcan y respeten en todo momento el deber de confidencialidad aquí establecido. El Seller responderá directamente ante Lyrium por cualquier daño o perjuicio —previsto o imprevisto— que se derive del incumplimiento de esta obligación, ya sea por su propia conducta o por la de las personas vinculadas a él, con independencia de si dicho incumplimiento fue voluntario o involuntario. A tal efecto, el Seller se compromete a suscribir con dichas personas los acuerdos internos que resulten necesarios para hacer exigibles las restricciones aquí contenidas.</p>

    <p class="lc-item-num"><span class="lc-num">14.7.</span> No obstante todo lo anterior, no será considerada confidencial aquella información que se encuentre en alguno de los siguientes supuestos:</p>

    <p class="lc-letter-item">a) Sea de dominio público o pase a serlo por razones no imputables a ninguna de las partes.</p>
    <p class="lc-letter-item">b) Hubiera estado en posesión de la parte receptora con anterioridad, siempre que esta pueda acreditarlo mediante evidencia fehaciente.</p>
    <p class="lc-letter-item">c) Haya sido puesta en conocimiento de la parte receptora, de buena fe, por un tercero que cuente con la facultad legal para divulgarla.</p>

    <p class="lc-p">Sin perjuicio de lo anterior, Lyrium podrá compartir con terceros la información de carácter público relativa a los Productos y/o Servicios del Seller, con el propósito de promoverlos y fortalecer el posicionamiento de su marca en el mercado. No obstante, Lyrium deberá mantener estricta reserva sobre toda aquella información del Seller a la que acceda y que revista carácter confidencial, incluyendo, sin limitarse a ello, la propiedad industrial, el know-how, los datos sensibles y cualquier otro antecedente cuya divulgación no autorizada pudiera ocasionar un perjuicio al Seller.</p>

    <p class="lc-p">En caso de que Lyrium divulgue información sensible o privada del Seller, o incumpla su deber de confidencialidad, el Acuerdo quedará resuelto de pleno derecho, sin necesidad de declaración judicial previa.</p>

    {{-- ════════════════════════ 15. DÉCIMO QUINTA ════════════════════════ --}}
    <p class="lc-clausula">Décimo Quinta: Relación entre las Partes</p>

    <p class="lc-item-num"><span class="lc-num">15.1.</span> Las Partes reconocen que la firma del presente Acuerdo, junto con la aceptación de los Términos y Condiciones Generales para Sellers, se limita exclusivamente a regular la relación comercial prevista en este instrumento, sin que ello suponga, en forma alguna, la constitución de una asociación, sociedad, joint venture, vínculo laboral o cualquier otra relación de naturaleza análoga entre Lyrium y el Seller. En tal sentido, ninguno de los derechos u obligaciones derivados de este Acuerdo o de los referidos Términos y Condiciones deberá interpretarse como propio de un contrato de agencia comercial, corretaje, comisión u otra figura similar, ni generar la aplicación de las reglas que les resulten inherentes. El Seller declara, además, contar con organización propia, recursos humanos, administrativos, financieros y operativos suficientes para el desarrollo independiente de sus actividades, actuando con autonomía absoluta respecto de Lyrium.</p>

    <p class="lc-item-num"><span class="lc-num">15.2.</span> Las Partes dejan constancia de que el presente Acuerdo y los Términos y Condiciones Generales para Sellers tienen por objeto exclusivo la regulación de lo establecido en la Cláusula Tercera del presente instrumento, sin que de su contenido se desprenda subordinación, dependencia, exclusividad laboral ni relación de trabajo alguna con Lyrium. En consecuencia, el Seller asume la total responsabilidad frente a cualquier tercero por reclamaciones, derechos, pretensiones o contingencias que pudieran derivarse de una eventual invocación de normas laborales, previsionales, de seguridad y salud en el trabajo, o de cualquier otra disposición vinculada, directa o indirectamente, a una relación laboral. Asimismo, el Seller libera de toda responsabilidad a Lyrium, así como a sus matrices, subsidiarias, afiliadas, accionistas, directores, gerentes, funcionarios, representantes, empleados, asesores, subcontratistas y agentes, respecto de cualquier consecuencia que pudiera originarse por dicha eventual vinculación.</p>

    {{-- ════════════════════════ 16. DÉCIMO SEXTA ════════════════════════ --}}
    <p class="lc-clausula">Décimo Sexta: Publicidad de la Plataforma</p>

    <p class="lc-item-num"><span class="lc-num">16.1.</span> Al formar parte de Lyrium Biomarketplace, el Seller concede a Lyrium una autorización expresa, amplia y no exclusiva para hacer uso de su marca con propósitos publicitarios, tanto dentro de la plataforma como en los canales digitales y redes sociales oficiales de Lyrium. Esta autorización tiene como objetivo potenciar la visibilidad de los productos del Seller y ampliar sus posibilidades de crecimiento comercial dentro del ecosistema Lyrium.</p>

    <p class="lc-item-num"><span class="lc-num">16.2.</span> La integración de la marca del Seller dentro de la sección de marcas destacadas, ubicada en la página principal de la plataforma Lyrium Biomarketplace, donde se exhibe la marca de determinados Sellers, queda sujeta a la entera discreción de Lyrium, quien evaluará para tal efecto según su criterio. La inclusión en esta sección no implica costo adicional para el Seller ni genera derecho alguno a permanencia continua, pudiendo Lyrium modificar en cualquier momento los criterios de selección o las marcas exhibidas.</p>

    <p class="lc-item-num"><span class="lc-num">16.3.</span> De forma independiente, la plataforma cuenta también con una sección de banners destacados dentro de su página principal. La integración de la marca del Seller en dicha sección sí requiere el pago de una tarifa publicitaria a favor de Lyrium, conforme a las condiciones comerciales que esta última establezca. Sin embargo, el Seller reconoce y acepta que dicho pago no garantiza la exhibición permanente ni exclusiva de su banner, ya que su presentación se sujeta a un sistema de horarios y cupos rotativos, mediante el cual los banners de los distintos Sellers participantes se muestran de forma alternada conforme a la disponibilidad y organización que Lyrium determine para dicha sección.</p>

    <p class="lc-item-num"><span class="lc-num">16.4.</span> Adicionalmente, Lyrium pone a disposición del Seller la posibilidad de acceder a servicios de publicidad personalizados, independientes de las campañas generales de marketing de la plataforma. Estos servicios tienen un costo adicional y se rigen por las condiciones comerciales que Lyrium defina y comunique oportunamente para cada caso.</p>

    <p class="lc-item-num"><span class="lc-num">16.5.</span> Para la creación del material visual necesario en el desarrollo de sus actividades publicitarias dentro de la plataforma, incluyendo imágenes de productos, banners, piezas promocionales y material para servicios, el Seller podrá optar por trabajar con el equipo de diseño gráfico y publicidad de Lyrium, o bien con su propio equipo creativo. En caso de elegir los servicios de diseño de Lyrium, estos estarán sujetos a las condiciones y tarifas que Lyrium establezca para tal efecto. Independientemente de la opción elegida, todo material producido deberá cumplir con los estándares y lineamientos gráficos definidos por Lyrium para su plataforma.</p>

    <p class="lc-item-num"><span class="lc-num">16.6.</span> En cuanto al uso del material publicitario, Lyrium autoriza al Seller a difundir y compartir fuera de la plataforma el material publicitario dispuesto o alojado dentro de Lyrium Biomarketplace, incluyendo imágenes de productos, banners, piezas promocionales y material para servicios, ya sea que este haya sido elaborado por el equipo de diseño de Lyrium o por el propio equipo creativo del Seller, con el fin de promocionar su marca. Sin embargo, dicha autorización no faculta al Seller para modificar, editar, alterar o adaptar en ninguna forma el material que incorpore la marca LYRIUM, la cual deberá ser utilizada en todo momento tal y como fue originalmente dispuesta por Lyrium. Del mismo modo, el Seller podrá registrar en su tienda virtual, a través de Lyrium Biomarketplace, las direcciones de sitios web, redes sociales u otros canales propios con fines de identificación de marca. No obstante, queda expresamente prohibido que utilice la plataforma Lyrium Biomarketplace, su material publicitario o cualquier recurso derivado de esta como medio para incentivar, invitar o inducir a los usuarios a concretar compras en sitios web propios, tiendas externas u otras plataformas de comercio ajenas a Lyrium, ya sea de forma directa, mediante mensajes, códigos QR o cualquier otro mecanismo cuya finalidad sea desviar el tráfico o las transacciones fuera del entorno de Lyrium Biomarketplace.</p>

    {{-- ════════════════════════ 17. DÉCIMO SÉPTIMA ════════════════════════ --}}
    <p class="lc-clausula">Décimo Séptima: Ley Aplicable y Solución de Controversias</p>

    <p class="lc-item-num"><span class="lc-num">17.1.</span> Para todo aquello que no haya sido expresamente regulado por las partes en el presente documento, ambas acuerdan someterse a las disposiciones contenidas en el Código de Comercio, la Ley General de Sociedades y demás normas legales que resulten aplicables en materia de comisión mercantil.</p>

    <p class="lc-item-num"><span class="lc-num">17.2.</span> La ley que rige los presentes Términos y Condiciones, así como el mecanismo para la resolución de cualquier conflicto, serán los establecidos en el Acuerdo suscrito entre las partes. Cualquier controversia que surja en relación con la interpretación, ejecución, validez o cumplimiento de dicho Acuerdo será resuelta ante los Juzgados y Tribunales competentes de la ciudad de Piura.</p>

    <p class="lc-item-num"><span class="lc-num">17.3.</span> Los presentes Términos y Condiciones son vinculantes en su totalidad para ambas partes y se tendrán por aceptados desde la fecha en que el Seller suscriba el Acuerdo correspondiente.</p>

    <p class="lc-item-num"><span class="lc-num">17.4.</span> En señal de conformidad y plena aceptación de lo aquí establecido, el presente documento y el Acuerdo correspondiente podrán ser suscritos bajo 2 (dos) modalidades de registro: (i) De forma presencial, mediante la intervención de un agente comercial de Lyrium, quien asistirá al solicitante en la formalización del registro y en la suscripción física del documento en dos ejemplares de igual valor y contenido; o (ii) De forma virtual, mediante el agente virtual habilitado en la plataforma Lyrium Biomarketplace, a través del cual el solicitante completará el proceso de registro y la aceptación electrónica de los presentes Términos y Condiciones y del Acuerdo correspondiente. En ambos casos, la aceptación producida tendrá plena validez y eficacia jurídica, vinculando a las partes en los mismos términos y condiciones.</p>

    {{-- ── Pie ──────────────────────────────────────────────────────────────── --}}
    <div class="lc-footer">
        <p class="lc-footer-text">
            Has revisado la totalidad de los Términos y Condiciones Generales para Sellers de Lyrium Biomarketplace.
        </p>
    </div>

</div>