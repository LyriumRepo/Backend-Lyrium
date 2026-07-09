{{--
    contratos/preview.blade.php
    ─────────────────────────────────────────────────────────────────────────
    Vista COMPLETA del Acuerdo Comercial pre-llenado con los datos del SELLER.
    Incluye el texto íntegro de las 15 cláusulas con su numeración decimal
    real (1.1, 3.4, etc.), verificada visualmente contra el Word original.

    Se excluye únicamente lo que va después de las cláusulas: selección de
    plan, firmas, huellas y Anexo N°1 (esa sección la completa el equipo de
    Lyrium después).

    Variables disponibles (inyectadas por ContratoService::prepararDatos):
      $nombre_comercial, $ruc, $domicilio_legal, $contacto, $dni,
      $telefono, $email, $fecha, $numero_contrato
--}}

{{-- ── Estilos: variables light + overrides dark (clase .dark en <html>) ── --}}
<style>
    .lyrium-contrato {
        --lc-text:        #1a1a1a;
        --lc-text-muted:  #555555;
        --lc-text-faint:  #888888;
        --lc-accent:      #4a7a5a;
        --lc-accent-head: #1a3a2a;
        --lc-bg:          transparent;
        --lc-bg-lyrium:   #f8faf8;
        --lc-bg-seller:   #f0f7f0;
        --lc-border-head: #1a3a2a;
        --lc-border-card: #d0e8d0;
        --lc-border-seller: #4a7a5a;
        --lc-border-foot: #cccccc;
        --lc-highlight:   #f0f7f0;
        --lc-link:        #4a7a5a;
        --lc-clausula-border: #4a7a5a;
    }

    .dark .lyrium-contrato {
        --lc-text:        #e2e8e4;
        --lc-text-muted:  #9ab5a2;
        --lc-text-faint:  #6b8f78;
        --lc-accent:      #8FC3A1;
        --lc-accent-head: #8FC3A1;
        --lc-bg:          transparent;
        --lc-bg-lyrium:   rgba(143,195,161,0.05);
        --lc-bg-seller:   rgba(143,195,161,0.09);
        --lc-border-head: #8FC3A1;
        --lc-border-card: rgba(143,195,161,0.2);
        --lc-border-seller: #8FC3A1;
        --lc-border-foot: rgba(143,195,161,0.2);
        --lc-highlight:   rgba(143,195,161,0.12);
        --lc-link:        #8FC3A1;
        --lc-clausula-border: #8FC3A1;
    }

    .lyrium-contrato {
        font-family: 'Georgia', 'Times New Roman', serif;
        font-size: 12px;
        line-height: 1.7;
        color: var(--lc-text);
        max-width: 100%;
    }

    /* Encabezado */
    .lc-header {
        text-align: center;
        margin-bottom: 24px;
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
    .lc-header-num {
        margin: 8px 0 0;
        font-size: 10px;
        color: var(--lc-text-muted);
    }

    /* Cuerpo */
    .lc-p {
        margin: 0 0 14px;
        text-align: justify;
    }
    .lc-p-lg {
        margin: 0 0 20px;
        text-align: justify;
    }

    /* Dato del usuario destacado */
    .lc-dato {
        background-color: var(--lc-highlight);
        padding: 1px 4px;
        border-radius: 2px;
    }

    /* Cláusulas */
    .lc-clausula {
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        margin: 20px 0 8px;
        color: var(--lc-accent-head);
        border-left: 3px solid var(--lc-clausula-border);
        padding-left: 8px;
    }
    .lc-item {
        margin: 4px 0 4px 20px;
        text-align: justify;
    }

    /* Puntos numerados (1.1, 3.4, etc.) */
    .lc-item-num {
        margin: 0 0 10px;
        text-align: justify;
        padding-left: 30px;
        text-indent: -30px;
    }
    .lc-num {
        font-weight: bold;
        color: var(--lc-accent-head);
        margin-right: 2px;
    }

    /* Sub-viñetas planas (ej. dentro de 4.3) */
    .lc-sub-bullets {
        margin: 6px 0 10px 30px;
        padding: 0;
        list-style: disc;
    }
    .lc-sub-bullets li {
        margin: 2px 0;
        text-align: justify;
    }

    /* Sub-items en numeral romano (i)(ii)(iii) */
    .lc-roman-item {
        margin: 8px 0;
        text-align: justify;
        padding-left: 38px;
        text-indent: -38px;
    }

    /* Tarjetas de contacto */
    .lc-card-lyrium {
        margin: 8px 0 12px 16px;
        padding: 10px 14px;
        background: var(--lc-bg-lyrium);
        border: 1px solid var(--lc-border-card);
        border-radius: 4px;
    }
    .lc-card-seller {
        margin: 0 0 12px 16px;
        padding: 10px 14px;
        background: var(--lc-bg-seller);
        border: 2px solid var(--lc-border-seller);
        border-radius: 4px;
    }
    .lc-card-label {
        margin: 0 0 4px;
        font-weight: bold;
        font-size: 11px;
        color: var(--lc-accent-head);
    }
    .lc-card-row {
        margin: 2px 0;
        color: var(--lc-text);
    }

    /* Enlace */
    .lc-link {
        color: var(--lc-link);
    }

    /* Pie */
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
    .lc-footer-note {
        text-align: center;
        font-size: 10px;
        color: var(--lc-text-faint);
        font-style: italic;
        margin: 12px 0 0;
    }
</style>

<div class="lyrium-contrato">

    {{-- ── Encabezado ──────────────────────────────────────────────────────── --}}
    <div class="lc-header">
        <p class="lc-header-brand">Lyrium Biomarketplace</p>
        <h2 class="lc-header-title">
            Acuerdo Comercial de Prestación de Servicios<br>de la Plataforma Virtual Lyrium Biomarketplace
        </h2>
        @if($numero_contrato)
        <p class="lc-header-num">
            N° de Acuerdo: <strong>{{ $numero_contrato }}</strong>
        </p>
        @endif
    </div>

    {{-- ── Introducción / Partes del acuerdo ──────────────────────────────── --}}
    <p class="lc-p">
        Conste por el presente documento el <strong>Acuerdo de Prestación de Servicios de la Plataforma Virtual LYRIUM Biomarketplace</strong> (en adelante, el "<strong>Acuerdo</strong>") que celebran:
    </p>

    <p class="lc-p">
        <strong>LYRIUM E.I.R.L.</strong>, con R.U.C. N° 20612731838, con domicilio en Av. Las Amapolas Mz. KD Lote 05 Urb. Santa Margarita Etapa I, Distrito Veintiséis de Octubre, Provincia y Departamento de Piura, representada por LUIS ENRIQUE PARDO FIGUEROA PALOMINO, identificado con DNI N° 72038599, con poder inscrito en Registros Públicos en la Partida Registral 11290578, con N° telefónico 969343913 y correo electrónico administracion@lyriumbiomarketplace.com, a quien en adelante se denominará <strong>LYRIUM</strong>; y de otro lado
        <strong class="lc-dato">{{ $nombre_comercial }}</strong>,
        con RUC N° <strong class="lc-dato">{{ $ruc }}</strong>,
        con domicilio en <strong class="lc-dato">{{ $domicilio_legal }}</strong>,
        representado por <strong class="lc-dato">{{ $contacto }}</strong>,
        identificado con DNI N° <strong class="lc-dato">{{ $dni }}</strong>;
        a quien en adelante se denominará el <strong>SELLER</strong>, que acuerdan en los siguientes términos:
    </p>

    <p class="lc-p">
        En adelante <strong>LYRIUM</strong> y el <strong>SELLER</strong> denominados cada uno la "<strong>Parte</strong>" y conjuntamente las "<strong>Partes</strong>".
    </p>

    <p class="lc-p-lg">
        El presente Acuerdo se celebra en los términos y condiciones que se detallan a continuación.
    </p>

    {{-- ════════════════════════ 1. PRIMERO ════════════════════════ --}}
    <p class="lc-clausula">PRIMERO: Antecedentes</p>

    <p class="lc-item-num"><span class="lc-num">1.1.</span> <strong>LYRIUM</strong> ha desarrollado la plataforma denominada "Lyrium Biomarketplace", alojada en el sitio web de titularidad exclusiva de <strong>LYRIUM</strong>, <a href="http://www.lyriumbiomarketplace.com" class="lc-link">www.lyriumbiomarketplace.com</a> (en adelante, el "Sitio Web"). La plataforma Lyrium Biomarketplace estará puesta a disposición de aquellos proveedores dedicados a la comercialización de productos y/o servicios, además de ofrecer prestación de servicios dentro del territorio nacional de la República del Perú, que son autorizados por <strong>LYRIUM</strong> para que, por medio de su utilización, y por su propia cuenta y exclusivo riesgo, realicen la comercialización directa de productos y/o servicios de su propiedad y/o prestación de servicios, que mantienen en stock, a los consumidores que visiten el Sitio Web.</p>

    <p class="lc-item-num"><span class="lc-num">1.2.</span> La plataforma "Lyrium Biomarketplace" se encuentra actualmente en una etapa preoperativa, la cual iniciará sus operaciones tentativamente el <strong>01 del mes de mayo del año 2025</strong>.</p>

    <p class="lc-item-num"><span class="lc-num">1.3.</span> El <strong>SELLER</strong> es una empresa dedicada a la comercialización de diversos productos y/o prestación de servicios dentro del territorio nacional, y que cuenta con la experiencia suficiente, los medios técnicos y humanos y las autorizaciones necesarias para el desempeño de su actividad comercial en el país o República del Perú.</p>

    <p class="lc-item-num"><span class="lc-num">1.4.</span> En tal sentido, el <strong>SELLER</strong> ha manifestado a <strong>LYRIUM</strong> su interés en ofrecer sus productos y/o prestar sus servicios en el Sitio Web por medio de la modalidad Lyrium Biomarketplace, utilizando dicho servicio de conformidad con los Términos y Condiciones aplicables a los SELLERS (en adelante, los "Términos y Condiciones"), que las Partes declaran conocer y aceptar en su totalidad y que forman parte integrante de este Acuerdo para todos los efectos. En caso de discrepancia entre lo establecido entre el Acuerdo y los Términos y Condiciones, primará lo dispuesto en el Acuerdo.</p>

    {{-- ════════════════════════ 2. SEGUNDO ════════════════════════ --}}
    <p class="lc-clausula">SEGUNDO: Objeto del Contrato</p>

    <p class="lc-item-num"><span class="lc-num">2.1.</span> Mediante el presente Acuerdo, <strong>LYRIUM</strong> acuerda prestar al <strong>SELLER</strong> los servicios correspondientes al uso del Sitio Web desarrollado (en adelante, los "Servicios"), así como aquellos que resulten necesarios para la adecuada comercialización de los Productos y/o servicios, así como la prestación de servicios a través de Lyrium Biomarketplace, bajo los estándares establecidos por <strong>LYRIUM</strong>. En contraprestación, el <strong>SELLER</strong> se obliga a pagar a <strong>LYRIUM</strong> una comisión de cada venta realizada, la cual será debitada automáticamente a través de la pasarela de pagos de <strong>LYRIUM</strong>; así como al pago de una suscripción mensual, de corresponder de acuerdo con el plan contratado (Anexo N°1). Asimismo, el <strong>SELLER</strong> será registrado como proveedor facultado a comercializar productos y/o prestar servicios a través del Sitio Web, en Lyrium Biomarketplace, conforme a las obligaciones asumidas en el presente Contrato y a los Términos y Condiciones, los productos de su propiedad y/o servicios aprobados por <strong>LYRIUM</strong>.</p>

    <p class="lc-item-num"><span class="lc-num">2.2.</span> Dado que la plataforma Lyrium Biomarketplace se encuentra en una etapa pre-operativa, los Servicios prestados por <strong>LYRIUM</strong> hacia el <strong>SELLER</strong> y las Obligaciones del <strong>SELLER</strong> hacia <strong>LYRIUM</strong> (ver Cláusula Cuarta) iniciarán a partir de la fecha de activación de la plataforma Lyrium Biomarketplace, la cual será fijada tentativamente para el <strong>01 del mes de mayo del año 2025</strong>, fecha en la cual la plataforma iniciaría operaciones formal y oficialmente.</p>

    {{-- ════════════════════════ 3. TERCERO ════════════════════════ --}}
    <p class="lc-clausula">TERCERO: Contraprestación y Facturación</p>

    <p class="lc-item-num"><span class="lc-num">3.1.</span> Las partes acuerdan que <strong>LYRIUM</strong> realizará el servicio de recaudación de pagos a través de su plataforma Lyrium Biomarketplace.</p>

    <p class="lc-item-num"><span class="lc-num">3.2.</span> Como contraprestación por los Servicios, el <strong>SELLER</strong> pagará a <strong>LYRIUM</strong> una comisión sobre el valor de venta de cada producto vendido y/o servicio prestado en Lyrium Biomarketplace. Las Partes entienden que el precio total pagado por cada cliente, es decir el precio de venta, incluye: (i) el valor de venta al público del producto y/o servicio del SELLER, y (ii) el Impuesto General a las Ventas del SELLER: <strong>Precio de Venta = Valor de Venta + IGV</strong>.</p>

    <p class="lc-item-num"><span class="lc-num">3.3.</span> La comisión cobrada por <strong>LYRIUM</strong> por uso de la plataforma Lyrium Biomarketplace es igual al <strong>15% del valor de venta: Comisión Lyrium = 15% × Valor de Venta (no incluye el IGV)</strong>.</p>

    <p class="lc-item-num"><span class="lc-num">3.4.</span> Las Partes acuerdan que <strong>LYRIUM</strong> se obliga a emitir un comprobante de pago (boleta o factura) por cada compra efectiva realizada por el consumidor sobre algún producto y/o servicio del SELLER, en el cual se indica la comisión cobrada por LYRIUM por concepto de uso de la plataforma Lyrium Biomarketplace. Este comprobante se enviará al correo electrónico que el SELLER hubiere registrado en la plataforma.</p>

    <p class="lc-item-num"><span class="lc-num">3.5.</span> Las Partes acuerdan que <strong>LYRIUM</strong> realizará el pago del precio de los productos y servicios del SELLER vendidos cada semana de la siguiente manera: cada lunes durante la vigencia del acuerdo se revisarán las ventas realizadas por el SELLER durante la semana anterior, y el total del ingreso de dichas ventas menos la comisión respectiva será pagado a la cuenta registrada por el SELLER en el plazo máximo de 03 días hábiles (miércoles de cada semana), por transferencia bancaria o a través del aplicativo BCP Banca Móvil.</p>

    {{-- ════════════════════════ 4. CUARTO ════════════════════════ --}}
    <p class="lc-clausula">CUARTO: Obligaciones del SELLER</p>

    <p class="lc-item-num"><span class="lc-num">4.1.</span> El <strong>SELLER</strong> manifiesta cumplir cabalmente con sus obligaciones a partir de la fecha de activación de la plataforma Lyrium Biomarketplace, fijada tentativamente para el <strong>01 del mes de mayo del año 2025</strong>.</p>

    <p class="lc-item-num"><span class="lc-num">4.2.</span> El <strong>SELLER</strong> se obliga a implementar completamente su espacio online, así como diseñar sus productos y/o servicios y cargarlos a Lyrium Biomarketplace en un plazo máximo de <strong>01 mes</strong>, plazo el cual iniciará después de la fecha de activación de la plataforma.</p>

    <p class="lc-item-num"><span class="lc-num">4.3.</span> El <strong>SELLER</strong> se obliga a cumplir con el mínimo de ventas establecidas por LYRIUM, obligación la cual iniciará después del año (doce meses) de la fecha de activación de la plataforma:</p>
    <ul class="lc-sub-bullets">
        <li>Empresas que venden productos: S/ 300 mensuales</li>
        <li>Empresas que venden servicios: S/ 400 mensuales</li>
    </ul>

    <p class="lc-item-num"><span class="lc-num">4.4.</span> Las obligaciones derivadas del Acuerdo y de los Términos y Condiciones, así como las que se deriven de toda norma de carácter imperativo o reglamentaria aplicada a la comercialización de los productos y/o prestación de los servicios, deberán realizarse empleando la máxima diligencia en su calidad de vendedor registrado en la plataforma Lyrium Biomarketplace.</p>

    <p class="lc-item-num"><span class="lc-num">4.5.</span> El <strong>SELLER</strong> declara y garantiza a LYRIUM que cuenta con la organización propia, experiencia, calificación, condiciones y capacidades, así como con la infraestructura, recursos financieros, técnicos y materiales, el personal calificado, y en general con todos los recursos necesarios para comercializar los productos y/o prestar los servicios en el Sitio Web, a través de Lyrium Biomarketplace, de manera integral, autónoma e independiente, bajo su cuenta, costo y riesgo, cumpliendo en tiempo y forma con todas sus obligaciones, incluidas las órdenes de compra que reciba.</p>

    <p class="lc-item-num"><span class="lc-num">4.6.</span> El <strong>SELLER</strong> reconoce y acepta expresamente que uno de los objetivos principales del Acuerdo es entregar a los consumidores un servicio de la más alta calidad en relación con la comercialización de los Productos y/o prestación de los servicios. En consecuencia, será obligación del SELLER mantener durante toda la vigencia del Acuerdo los más altos estándares de calidad y servicio, así como el servicio de post-venta (devolución, reposición, reembolso, cambio o garantía de los Productos; atención de quejas y/o reclamos en el caso de servicios), cumpliendo con las directrices y niveles de calidad establecidos por LYRIUM y comunicados al SELLER a través de la plataforma o mediante correo electrónico al Administrador del Contrato.</p>

    <p class="lc-item-num"><span class="lc-num">4.7.</span> El mismo deber de cuidado aplicará para los despachos de los Productos, sea que el despacho lo realice directamente el SELLER o, en caso contrate a un operador logístico, en ambos supuestos será el SELLER quien asumirá total responsabilidad por la integridad de los productos frente a LYRIUM y el consumidor.</p>

    <p class="lc-item-num"><span class="lc-num">4.8.</span> Para comercializar sus Productos y/o ofrecer los servicios en el Sitio Web, el SELLER únicamente utilizará personal competente, experimentado y capacitado para cumplir con los niveles de calidad y servicio exigidos por LYRIUM.</p>

    <p class="lc-item-num"><span class="lc-num">4.9.</span> El <strong>SELLER</strong> deberá mantener vigentes durante todo el plazo de vigencia del presente Acuerdo todas las autorizaciones y demás permisos requeridos para la comercialización de los Productos y/o prestación de los servicios en el Sitio Web, a través de Lyrium Biomarketplace.</p>

    <p class="lc-item-num"><span class="lc-num">4.10.</span> <strong>LYRIUM</strong> se reserva el derecho de llevar a cabo cualquier revisión posterior al SELLER respecto de los asuntos mencionados precedentemente; en caso LYRIUM así lo requiera, el SELLER deberá mostrar los documentos que acrediten el cumplimiento de sus obligaciones y el mantenimiento de la calidad en su servicio.</p>

    <p class="lc-item-num"><span class="lc-num">4.11.</span> El <strong>SELLER</strong> reconoce que es el único y exclusivo responsable frente a LYRIUM por la consignación de la información respecto de los productos y/o servicios que promocionará o exhibirá en su tienda o espacio virtual; en caso existan errores en la consignación de la información (precios, promociones, stock, características, alcances, entre otros) imputables a éste, asumirá dicho error, no siendo posible modificar la orden por ningún motivo.</p>

    <p class="lc-item-num"><span class="lc-num">4.12.</span> El <strong>SELLER</strong> reconoce que es el único y exclusivo responsable del cumplimiento de las obligaciones legales que se deriven de la relación jurídica entre el SELLER y su personal, funcionarios, agentes, representantes, contratistas o cualquier otra persona que contrate con ocasión del cumplimiento de sus actividades, garantizando que dichas personas se encuentran contratadas con sujeción a la legislación laboral vigente.</p>

    <p class="lc-item-num"><span class="lc-num">4.13.</span> El <strong>SELLER</strong> reconoce que es el único y exclusivo responsable frente a LYRIUM del cumplimiento de las obligaciones asumidas en el presente Acuerdo, respondiendo incluso por los actos u omisiones de su personal, funcionarios, agentes, representantes, contratistas o cualquier otra persona que contrate con ocasión del cumplimiento de sus actividades. Sin embargo, el SELLER no será responsable por los incumplimientos generados por causas no imputables, en cuyo caso se procederá conforme a los artículos 1315° y 1316° del Código Civil.</p>

    {{-- ════════════════════════ 5. QUINTO ════════════════════════ --}}
    <p class="lc-clausula">QUINTO: Despacho</p>

    <p class="lc-item-num"><span class="lc-num">5.1.</span> El <strong>SELLER</strong> se obliga a despachar los productos de manera directa o, en su defecto, contratar a un operador logístico, quien se encargará del transporte y entrega del producto adquirido con los estándares adecuados para realizar tal tarea. En ambos casos, el SELLER será el único responsable por la coordinación y envío de los mismos desde su lugar de origen al domicilio de los consumidores, no existiendo para LYRIUM responsabilidad alguna en el proceso de coordinación, despacho y entrega de los Productos. LYRIUM estará facultado a supervisar que el envío de los Productos y la realización de los Servicios sea conforme a los estándares establecidos, no siendo responsable en relación a ningún aspecto del despacho y entrega del Producto.</p>

    {{-- ════════════════════════ 6. SEXTO (era "SÉPTIMO" en el Word) ════════════════════════ --}}
    <p class="lc-clausula">SEXTO: Vigencia, Resolución y Eliminación de Cuenta del SELLER</p>

    <p class="lc-item-num"><span class="lc-num">6.1.</span> El presente Acuerdo tendrá una duración de un (1) año contado desde la fecha de activación de la plataforma (tentativamente el 01 de mayo de 2025). Transcurrido este plazo, el Acuerdo se renovará automáticamente por períodos iguales y sucesivos de un (1) año cada uno, salvo que cualquiera de las Partes manifieste su intención de no perseverar en el mismo, mediante comunicación escrita enviada con al menos treinta (30) días corridos de anticipación a la fecha de término del Acuerdo o de la prórroga respectiva.</p>

    <p class="lc-item-num"><span class="lc-num">6.2.</span> Sin perjuicio de lo anterior, <strong>LYRIUM</strong> podrá en cualquier momento durante la vigencia de este Acuerdo o de cualquiera de sus prórrogas, previa justificación, poner término al mismo mediante comunicación escrita remitida al domicilio de la otra Parte, con una anticipación no menor de quince (15) días calendario, sin que ello genere indemnización alguna a favor de cualquiera de las Partes ni pago de algún monto adicional.</p>

    <p class="lc-item-num"><span class="lc-num">6.3.</span> De conformidad con el artículo 1430° del Código Civil, el incumplimiento del SELLER de cualquiera de las obligaciones establecidas en el Acuerdo dará derecho a LYRIUM a poner término anticipado e inmediato al Acuerdo, de pleno derecho y sin incurrir en responsabilidad alguna, mediante comunicación escrita que informe la causa del incumplimiento, sin necesidad de declaración judicial alguna, y sin que esto genere indemnización a favor del SELLER. Esta resolución de pleno derecho ocurrirá sin perjuicio de las acciones de responsabilidad que LYRIUM pueda entablar contra el SELLER por los daños y perjuicios causados por el incumplimiento. El mismo derecho de resolución automática le asiste al SELLER ante cualquier incumplimiento de las obligaciones de LYRIUM.</p>

    <p class="lc-item-num"><span class="lc-num">6.4.</span> Terminado el Acuerdo por cualquier causa, <strong>LYRIUM</strong> procederá a dar de baja al SELLER de su plataforma Lyrium Biomarketplace, eliminando todos los productos y/o servicios que hubieren sido publicados por éste en el Sitio Web, y procediendo a la entrega de los montos recaudados por LYRIUM como consecuencia de la prestación de los Servicios, en los plazos que aplican conforme se establece en el presente documento.</p>

    <p class="lc-item-num"><span class="lc-num">6.5.</span> La resolución del Acuerdo también podrá producirse en caso que <strong>LYRIUM</strong> compruebe con evidencias y calificaciones negativas de los consumidores, en un máximo de 3 (tres) ocasiones o eventos, el incumplimiento del SELLER en cualquiera de los momentos de atención y servicio a sus clientes y consumidores.</p>

    {{-- ════════════════════════ 7. SÉPTIMO (era "OCTAVO" en el Word) — Comunicaciones ════════════════════════ --}}
    <p class="lc-clausula">SÉPTIMO: Comunicaciones</p>

    <p class="lc-item-num"><span class="lc-num">7.1.</span> Para efectos de lo dispuesto en el presente documento, las Partes designan como administradores del Acuerdo y puntos de contacto a las siguientes personas:</p>

    <div class="lc-card-lyrium">
        <p class="lc-card-label">Administrador del Contrato por parte de LYRIUM:</p>
        <p class="lc-card-row">Nombre: &nbsp;&nbsp;<strong>LUIS ENRIQUE PARDO FIGUEROA PALOMINO</strong></p>
        <p class="lc-card-row">Teléfono: &nbsp;<strong>937093420</strong></p>
        <p class="lc-card-row">E-mail: &nbsp;&nbsp;&nbsp;<strong>administracion@lyriumbiomarketplace.com</strong></p>
    </div>

    <div class="lc-card-seller">
        <p class="lc-card-label">Administrador del Contrato por parte del SELLER:</p>
        <p class="lc-card-row">Nombre: &nbsp;&nbsp;<strong>{{ $contacto }}</strong></p>
        <p class="lc-card-row">Teléfono: &nbsp;<strong>{{ $telefono }}</strong></p>
        <p class="lc-card-row">E-mail: &nbsp;&nbsp;&nbsp;<strong>{{ $email }}</strong></p>
    </div>

    <p class="lc-item-num"><span class="lc-num">7.2.</span> Las Partes acuerdan que los Administradores del Contrato podrán ser modificados a través del envío de una comunicación formal con un plazo no menor de 7 días antes de que dicho cambio surta plenos efectos.</p>

    {{-- ════════════════════════ 8. OCTAVO (era "NOVENO" en el Word) ════════════════════════ --}}
    <p class="lc-clausula">OCTAVO: De los Términos y Condiciones Generales para SELLERS</p>

    <p class="lc-item-num"><span class="lc-num">8.1.</span> El <strong>SELLER</strong> acepta y reconoce que los Términos y Condiciones Generales para SELLERs le resultan plenamente aplicables. Sin perjuicio de ello, en caso de duda o contradicción entre el presente Acuerdo y los Términos y Condiciones Generales para SELLERs, primará lo establecido en el Acuerdo.</p>

    <p class="lc-item-num"><span class="lc-num">8.2.</span> El <strong>SELLER</strong> acepta de forma anticipada que cualquier modificación que LYRIUM realice a los Términos y Condiciones Generales para SELLERs le resultará aplicable, y se entenderá incorporada al presente Contrato al día siguiente de ser comunicada al SELLER por parte de LYRIUM, a través de la plataforma o mediante correo electrónico a la persona de contacto establecida en este documento. En caso el SELLER no esté de acuerdo con las modificaciones, tendrá derecho a resolver el presente Contrato mediante comunicación escrita, en un plazo máximo de quince (15) días luego de recibida la comunicación de modificación. En dicho supuesto, la resolución del Acuerdo se dará una vez transcurridos quince (15) días luego de la recepción de la carta por LYRIUM.</p>

    <p class="lc-item-num"><span class="lc-num">8.3.</span> El presente Acuerdo no podrá ser modificado ni ampliado, ni complementado en ningún sentido, salvo mediante documento por escrito suscrito entre las Partes.</p>

    {{-- ════════════════════════ 9. NOVENO (era "DÉCIMO" en el Word) ════════════════════════ --}}
    <p class="lc-clausula">NOVENO: Tributos</p>

    <p class="lc-p">
        Cada una de las Partes asumirá los tributos creados o por crearse que le correspondan como consecuencia de la celebración y ejecución del presente Acuerdo, de conformidad con la legislación aplicable sobre la materia.
    </p>

    {{-- ════════════════════════ 10. DÉCIMO (era "DÉCIMO PRIMERA" en el Word) ════════════════════════ --}}
    <p class="lc-clausula">DÉCIMO: Indemnidades</p>

    <p class="lc-item-num"><span class="lc-num">10.1.</span> Las Partes acuerdan y declaran que el <strong>SELLER</strong> asumirá la responsabilidad y deberá indemnizar, defender, proteger y mantener indemne a LYRIUM, su gerencia, socios, asesores, subcontratistas y/o agentes, en caso LYRIUM se viera involucrada, como parte o tercero, en algún reclamo o denuncia relacionada al incumplimiento de la normativa en materia de publicidad, competencia desleal, protección al consumidor, propiedad intelectual e industrial y/o, en general, al incumplimiento de cualquier obligación que sea objeto de investigación, fiscalización o sanción por parte del INDECOPI, respecto a los Productos que comercialice y/o servicios que preste en el Sitio Web.</p>

    <p class="lc-item-num"><span class="lc-num">10.2.</span> De forma específica, LYRIUM no tendrá responsabilidad respecto de lo siguiente, siempre que la infracción no haya sido cometida por personal de LYRIUM:</p>
    <p class="lc-roman-item">(i) En caso se impusiera a LYRIUM una multa, denuncia y/o sanción, o se viera involucrada en algún reclamo, denuncia, demanda y/o controversia, por infracciones que puedan interponerse con ocasión o causa de daños, defectos y/o falta de idoneidad de los Productos y/o servicios ofrecidos al público en el Sitio Web;</p>
    <p class="lc-roman-item">(ii) En caso se impusiera a LYRIUM una multa, denuncia y/o sanción, o se viera involucrada en algún reclamo, denuncia, demanda y/o controversia, relacionada al uso o titularidad de las marcas, patentes, nombres comerciales, lemas comerciales, software, derecho de autor en general, know-how, modelos, diseños o cualquier otro elemento de propiedad industrial y/o intelectual que identifiquen a los Productos y/o servicios, o que sean utilizados por el SELLER para la comercialización de los Productos y/o prestación de los servicios;</p>
    <p class="lc-roman-item">(iii) En caso se impusiera a LYRIUM una multa, denuncia y/o sanción, o se viera involucrada en algún reclamo, denuncia, demanda y/o controversia, por actos de competencia desleal o infracciones a la normativa en materia de publicidad.</p>

    <p class="lc-item-num"><span class="lc-num">10.3.</span> En ese sentido, el <strong>SELLER</strong> se obliga a asumir a su propio costo la defensa ante cualquier entidad administrativa y/o tribunal de la República del Perú por los reclamos, denuncias, demandas y/o controversias señalados en los numerales precedentes, siempre y cuando dichas supuestas infracciones no hayan sido cometidas por los servicios de LYRIUM. En este caso, si el SELLER no desarrollase la defensa dentro de los plazos que la ley establece, o si LYRIUM así lo considerara conveniente, LYRIUM podrá asumir la defensa, avenir o transar en el pleito a su propio criterio, siendo todos los gastos y pagos (incluidos aquellos por contratación de abogados) de cargo del SELLER, los cuales deberán ser sustentados por LYRIUM.</p>

    <p class="lc-item-num"><span class="lc-num">10.4.</span> La indemnidad establecida en esta cláusula sobrevivirá al vencimiento del Acuerdo o la resolución del mismo, quedando vigente hasta el vencimiento del plazo de prescripción administrativa.</p>

    {{-- ════════════════════════ 11. DÉCIMO PRIMERA (era "DÉCIMO SEGUNDA" en el Word) ════════════════════════ --}}
    <p class="lc-clausula">DÉCIMO PRIMERA: Caso Fortuito o Fuerza Mayor</p>

    <p class="lc-item-num"><span class="lc-num">11.1.</span> En caso de un evento de caso fortuito o fuerza mayor que ocasione que las Partes no puedan cumplir con las obligaciones asumidas en el presente contrato, dicho incumplimiento no será imputable a las partes y generará la suspensión del presente contrato durante el tiempo del evento no imputable.</p>

    <p class="lc-item-num"><span class="lc-num">11.2.</span> Para estos efectos, se considerará que existe un caso fortuito o fuerza mayor cuando se compruebe que las Partes, pese a sus mejores esfuerzos, no puedan cumplir con sus obligaciones debido a un hecho imprevisible, irresistible y extraordinario.</p>

    <p class="lc-item-num"><span class="lc-num">11.3.</span> La suspensión de la vigencia del presente Acuerdo se levantará cuando haya cesado el evento de caso fortuito o fuerza mayor.</p>

    {{-- ════════════════════════ 12. DÉCIMO SEGUNDA (era "DÉCIMO TERCERA" en el Word) ════════════════════════ --}}
    <p class="lc-clausula">DÉCIMO SEGUNDA: Protección de Datos Personales</p>

    <p class="lc-item-num"><span class="lc-num">12.1.</span> El <strong>SELLER</strong> declara que, con motivo de la ejecución del Acuerdo, podrá realizar el tratamiento de datos personales contenidos en los bancos de datos de titularidad de LYRIUM. El SELLER reconoce que LYRIUM es titular y responsable de los datos personales y que únicamente fueron proporcionados por LYRIUM o recolectados en nombre e interés de LYRIUM, para el cumplimiento del Acuerdo. En ese sentido, el SELLER recibirá y/o recopilará los datos personales en calidad de encargado de tratamiento, de acuerdo con los términos previstos en la Ley de Protección de Datos Personales, Ley N° 29733, y su Reglamento, Decreto Supremo N° 003-2013-JUS, o cualquier otra norma concordante, complementaria, modificatoria y/o sustitutoria (la "Legislación Aplicable en materia de Datos Personales").</p>

    <p class="lc-item-num"><span class="lc-num">12.2.</span> El <strong>SELLER</strong> se compromete a tratar los datos personales estrictamente para el cumplimiento de los fines del Acuerdo. En ese sentido, el SELLER observará en todo momento las instrucciones y pautas previstas en el Acuerdo y aquellas que, a su sola discreción, LYRIUM emita y que sean informadas al SELLER.</p>

    <p class="lc-item-num"><span class="lc-num">12.3.</span> El <strong>SELLER</strong> declara que, en aquellos casos en que recopile los datos personales para su posterior entrega a LYRIUM, éstos se captarán contando con el consentimiento de los titulares de los datos personales, quienes autorizarán el tratamiento de su información por parte de LYRIUM de manera libre, previa, expresa, inequívoca e informada; para, entre otros fines, efectuar las correspondientes gestiones y actividades conducentes o relacionadas al cumplimiento del Acuerdo.</p>

    <p class="lc-item-num"><span class="lc-num">12.4.</span> Asimismo, el <strong>SELLER</strong> declara que ha adoptado los niveles de seguridad apropiados para el resguardo de la información, respetando las medidas de seguridad técnica aplicables a cada categoría y tipo de tratamiento de los bancos de datos personales.</p>

    <p class="lc-item-num"><span class="lc-num">12.5.</span> <strong>LYRIUM</strong> se reserva el derecho de auditar los consentimientos y las medidas técnicas de seguridad aplicadas por el SELLER, en cumplimiento de la Legislación Aplicable en materia de Datos Personales.</p>

    <p class="lc-item-num"><span class="lc-num">12.6.</span> El <strong>SELLER</strong> no podrá transferir los datos personales a ningún tercero, bajo circunstancia alguna; salvo cuando alguna autoridad gubernamental lo solicite, mediante documento formal, para el ejercicio de algunas de sus funciones y/o en caso de subcontratación de terceros previamente autorizada por LYRIUM, para efectos del cumplimiento de las obligaciones del Acuerdo.</p>

    <p class="lc-item-num"><span class="lc-num">12.7.</span> El <strong>SELLER</strong> declara que los datos personales proporcionados por o recopilados para LYRIUM sólo serán conocidos y utilizados por los empleados que tengan la necesidad de conocer dicha información en el curso del cumplimiento de las obligaciones del SELLER materia del Acuerdo, debiendo éstos someterse a todas las disposiciones de confidencialidad descritas en el Acuerdo.</p>

    <p class="lc-item-num"><span class="lc-num">12.8.</span> El <strong>SELLER</strong> reconoce que será responsable por la difusión y/o tratamiento de los datos personales proporcionados por LYRIUM para fines distintos a los establecidos en el Acuerdo, salvo que la información se encuentre en poder del SELLER antes de que ésta haya sido proporcionada por o recolectada para LYRIUM.</p>

    <p class="lc-item-num"><span class="lc-num">12.9.</span> El <strong>SELLER</strong> se compromete a eliminar los datos personales al momento de la finalización del Acuerdo. Asimismo, el SELLER se obliga a destruir cualquier copia de los datos personales que mantenga en sus archivos que, producto de la ejecución del Acuerdo, haya podido realizar, sin importar el soporte en el que se encuentre, sean estos físicos, digitales o bajo cualquier otro mecanismo que exista o se cree.</p>

    {{-- ════════════════════════ 13. DÉCIMO TERCERA (era "DÉCIMO CUARTA" en el Word) ════════════════════════ --}}
    <p class="lc-clausula">DÉCIMO TERCERA: Prevención del Lavado de Activos</p>

    <p class="lc-p">
        En relación con las obligaciones derivadas del presente Acuerdo, el <strong>SELLER</strong> declara estar de acuerdo y garantiza que no ha violado y no violará las leyes vigentes de lucha contra el lavado de activos, financiamiento del terrorismo, corrupción y sus regulaciones; que la totalidad de su patrimonio e ingresos no provienen de actividades de lavado de activos ni de cualquier actividad ilícita; y que el destino de los ingresos generados por el presente Acuerdo no será utilizado para el financiamiento del terrorismo o para actividades delictivas.
    </p>
    <p class="lc-p">
        En el supuesto que el <strong>SELLER</strong> tome conocimiento de la existencia de hechos que pudieran impactar a LYRIUM generando algún tipo de responsabilidad civil, penal o reputacional, deberá informarlo dentro de las 48 horas de conocido el hecho, sin perjuicio de tomar las medidas necesarias para cesar los actos que pudieran poner en riesgo a LYRIUM.
    </p>
    <p class="lc-p">
        En el supuesto que el <strong>SELLER</strong> incumpla cualquiera de las obligaciones indicadas en la presente cláusula, ello facultará a LYRIUM, entre otras cosas, a resolver el contrato de pleno derecho de conformidad con lo establecido en el Código Civil.
    </p>

    {{-- ════════════════════════ 14. DÉCIMO CUARTA (era "DÉCIMO QUINTA" en el Word) ════════════════════════ --}}
    <p class="lc-clausula">DÉCIMO CUARTA: Domicilio</p>

    <p class="lc-item-num"><span class="lc-num">14.1.</span> Para la validez de todas las comunicaciones y notificaciones a las partes con motivo de la ejecución de este Acuerdo, se realizarán a la dirección electrónica de LYRIUM detallada en la introducción y a la dirección del SELLER con el dominio lyriumbiomarketplace.com.pe.</p>

    <p class="lc-item-num"><span class="lc-num">14.2.</span> La variación de los domicilios electrónicos surtirá efecto por comunicación escrita de fecha cierta y realizada con siete (7) días calendario de anticipación a la variación efectiva del domicilio, sin la cual queda entendido que los domicilios señalados en la introducción del presente Acuerdo son válidos para todos los efectos legales.</p>

    {{-- ════════════════════════ 15. DÉCIMO QUINTA (era "DÉCIMO SEXTO" en el Word) ════════════════════════ --}}
    <p class="lc-clausula">DÉCIMO QUINTA: Ley Aplicable y Solución de Controversias</p>

    <p class="lc-p">
        Las Partes acuerdan que el presente Acuerdo se rige por las leyes de la República del Perú.
    </p>
    <p class="lc-p">
        Asimismo, se establece que cualquier discrepancia, conflicto o controversia que pudiera surgir entre las Partes como consecuencia de la interpretación o ejecución de este documento, incluidas las relacionadas con su nulidad, anulabilidad, ineficacia e invalidez, así como cualquier efecto o consecuencia directa o indirecta vinculada a éste, sea de naturaleza contractual o extracontractual, serán resueltas de preferencia de común acuerdo.
    </p>
    <p class="lc-p">
        En caso dicha discrepancia, conflicto o controversia no pudiera resolverse de común acuerdo, las Partes renuncian al fuero de sus domicilios y se someten a la jurisdicción de los jueces y tribunales del Distrito, Provincia y Región de Piura.
    </p>

    {{-- ── Pie del acuerdo ──────────────────────────────────────────────────── --}}
    <div class="lc-footer">
        <p class="lc-footer-text">
            Has revisado la totalidad de las cláusulas del Acuerdo Comercial, generado el día <strong>{{ $fecha }}</strong>.
        </p>
        <p class="lc-footer-note">
            La selección de plan de afiliación, las firmas y huellas serán completadas por el equipo de Lyrium una vez procesada tu solicitud.
        </p>
    </div>

</div>