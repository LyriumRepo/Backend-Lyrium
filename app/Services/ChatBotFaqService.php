<?php

declare(strict_types=1);

namespace App\Services;

final class ChatBotFaqService
{
    private const SIMILARITY_THRESHOLD = 60;

    // Respuesta del asesor reutilizada en vender_f, comprar_9 y keyword directo "contáctame"
    private const ASESOR_RESPONSE = "👩🏻¡Por supuesto! En un momento te conectamos con uno de nuestros asesores comerciales para atender todas tus consultas. Por favor, mantente en línea con nosotros.";

    // Lyrio menu intents — checked before FAQs, no AI cost
    private const LILY_INTENTS = [
        'inicio' => [
            'keywords' => ['hola', 'buenas', 'consulta', 'que tal', 'holis', 'volver', 'inicio'],
            'response' => "¡Hola! 😊\nSoy Lyrio🌿, tu asistente virtual de LYRIUM BIOMARKETPLACE. Estoy aquí para hacer tu experiencia más fácil y saludable 🌱.\n¿En qué puedo ayudarte hoy? Puedes decirme si deseas \"vender\" o \"comprar\".\n\n¡Estoy listo para ayudarte! 🛍️✨",
        ],
        'vender_menu' => [
            'keywords' => ['vender', 'menu', 'menú'],
            'response' => "👩🏻 ¿Cómo puedo ayudarte a vender en Lyrium Biomarketplace?\nSelecciona una opción escribiendo la letra de tu interés. (Ej: A)\nA. 🌍Deseo el enlace al sitio web\nB. 🙋🏻Me interesa convertirme en vendedor\nC. 🏪¿Qué tipo de tiendas pueden registrarse?\nD. 🌱🛍️¿Qué productos y servicios se pueden vender?\nE. 📞Necesito información de contacto\nF. 🧑🏻Deseo la atención de un asesor comercial\n\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        ],
        'comprar_menu' => [
            'keywords' => ['comprar', 'atras'],
            'response' => "👩🏻 ¿Cómo puedo ayudarte a comprar en nuestro Biomarketplace?\n1. 🌍Deseo el enlace al sitio web\n2. 🌱🛍️¿Qué productos y servicios ofrecen?\n3. 🕙Necesito saber el horario de atención\n4. 💳Quiero conocer los métodos de pago\n5. 🛵🛍️Quisiera saber como funcionan los envíos\n6. 🔄🛍️Quisiera saber como funcionan las devoluciones\n7. 🔄💲Quisiera saber como funcionan los reembolsos\n8. 📞Necesito información de contacto\n9. 🧑🏻Deseo la atención de un asesor comercial\n\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        ],
        // Keyword directo: mencionado en la respuesta C del menú vender
        'contactame' => [
            'keywords' => ['contactame', 'contactame'],
            'response' => self::ASESOR_RESPONSE,
        ],
    ];

    // Opciones de vender (A-F) — activas solo cuando el bot acaba de mostrar vender_menu
    private const VENDER_OPTIONS = [
        'a' => "📈¡Aumenta tus ventas y tu mercado con nosotros!\n😊¡Bienvenido a Lyrium Biomarketplace!\n👉🏻 https://lyriumbiomarketplace.com/\n\n🔄 _Escriba \"menú\" para volver a las opciones de venta._\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        'b' => "🙋🏻¿Te gustaría ser parte de Lyrium Biomarketplace? ¡Es fácil!\n\nPuedes hacerlo de dos formas:\n1️⃣ Contactando con uno de nuestros asesores comerciales\n2️⃣ A través de nuestro sitio web:\n· Haciendo clic en registrarse\n· Introduciendo los datos solicitados\n· Espera nuestra confirmación como vendedor\n\n🔄 _Escriba \"menú\" para volver a las opciones de venta._\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        'c' => "🏪¡Forma parte de nuestra Biocomunidad!\nEn Lyrium Biomarketplace pueden registrarse personas naturales y/o jurídicas que ofrezcan productos o servicios relacionados con la salud y el bienestar en cualquiera de las categorías de nuestro Biomarketplace.\n\n👉🏻 Para más detalles, visita nuestra página principal: 👇🏻\nhttps://lyriumbiomarketplace.com/\n\n🔄 _Escriba \"contáctame\" si deseas que un asesor te contacte directamente._\n🔄 _Escriba \"menú\" para volver a las opciones de venta._\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        'd' => "🌱🛍️¡Descubre lo que puedes ofrecer!\nEn nuestro Biomarketplace, puedes vender productos y servicios enfocados en el bienestar y la salud, como suplementos, alimentos saludables, terapias naturales, servicios médicos y mucho más. ¡Conviértete en parte de nuestra comunidad saludable! 🌟\n\n👉🏻 Consulta todas las categorías: 👇🏻\nhttps://lyriumbiomarketplace.com/\n\n🔄 _Escriba \"menú\" para volver a las opciones de venta._\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        'e' => "🤗Estamos aquí para ti. Contáctanos a través de estos medios:\n\n· 📱Cel o Wsp: 937-093-420\n· 📞Tel: (073) 61-41-70\n· 📧Correo: administracion@lyriumbiomarketplace.com\n\n🔄 _Escriba \"menú\" para volver a las opciones de venta._\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        'f' => self::ASESOR_RESPONSE,
    ];

    // Opciones de comprar (1-9) — activas solo cuando el bot acaba de mostrar comprar_menu
    private const COMPRAR_OPTIONS = [
        '1' => "🌱¡Ten un estilo de vida mas saludable con nosotros!\n😊¡Bienvenido a Lyrium Biomarketplace!\n👉🏻 https://lyriumbiomarketplace.com/\n\n🔄 _Escriba \"atrás\" para volver a las opciones de compra._\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        '2' => "🌱🛍️¡Descubre todo lo que tenemos para ti!\nLyrium Biomarketplace te ofrece una variedad de productos y servicios enfocados para tu bienestar y la salud.\n\n👉🏻 Consulta todas las categorías: 👇🏻\nhttps://lyriumbiomarketplace.com/\n\n🔄 _Escriba \"atrás\" para volver a las opciones de compra._\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        '3' => "🕙¡Estamos disponibles para ti las 24/7, todo el año! 🌟 Pero te recordamos que cada una de nuestras tiendas tiene su propio horario de atención.\n\n🔄 _Escriba \"atrás\" para volver a las opciones de compra._\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        '4' => "💳Aceptamos tarjetas Nacionales de Visa, American Express, Master Card y Dinners Club.\n\n🔄 _Escriba \"atrás\" para volver a las opciones de compra._\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        '5' => "🛵🛍️ *Si compras productos:*\nCada tienda gestionará tu pedido según su horario y tiempos de envío. Podrás elegir entre opciones como recojo en tienda o entrega a domicilio. Para la entrega a domicilio, no olvides revisar las tarifas referenciales en el perfil de cada tienda antes de realizar tu compra.\n\n🩺 *Si adquieres servicios:*\nLa tienda se pondrá en contacto contigo después de la compra de tu servicio para ayudarte a elegir un especialista y agendar tu cita en el momento que mejor te convenga.\n\n🔄 _Escriba \"atrás\" para volver a las opciones de compra._\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        '6' => "🔄🛍️Toda devolución de productos es de entera responsabilidad y según las políticas de cada una de nuestras tiendas vendedoras registradas en nuestro Biomarketplace.\n\n🔄 _Escriba \"atrás\" para volver a las opciones de compra._\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        '7' => "🔄💲Todo reembolso de productos y servicios es de entera responsabilidad y según las políticas de cada una de nuestras tiendas vendedoras registradas en nuestro Biomarketplace.\n\n🔄 _Escriba \"atrás\" para volver a las opciones de compra._\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        '8' => "🤗Estamos aquí para ti. Contáctanos a través de estos medios:\n\n· 📱Cel o Wsp: 937-093-420\n· 📞Tel: (073) 61-41-70\n· 📧Correo: administracion@lyriumbiomarketplace.com\n\n🔄 _Escriba \"atrás\" para volver a las opciones de compra._\n🔄 _Escriba \"inicio\" para volver al principio de la atención ._",
        '9' => self::ASESOR_RESPONSE,
    ];

    private const FAQS = [
        'comprar_producto' => [
            'keywords' => ['como compro', 'como adquirir', 'comprar producto', 'realizar pedido', 'hacer un pedido', 'como pedir'],
            'terms'    => ['compra', 'adquirir', 'pedir', 'orden', 'producto'],
            'response' => 'Para comprar un producto en Lyrium: 1. Navega por las categorías o usa el buscador para encontrar el producto que deseas. 2. Haz clic en el producto para ver los detalles. 3. Selecciona la cantidad y añádelo al carrito. 4. Revisa tu carrito y haz clic en "Ir a pagar". 5. Completa tus datos de envío, selecciona un método de pago y confirma la compra. Recibirás una confirmación por correo electrónico y podrás dar seguimiento a tu pedido desde tu panel de usuario.',
        ],
        'contactar_vendedor' => [
            'keywords' => ['contactar vendedor', 'hablar con vendedor', 'comunicarme con vendedor', 'mensaje a vendedor', 'escribir vendedor'],
            'terms'    => ['contactar', 'comunicar', 'mensaje', 'hablar', 'chat'],
            'response' => 'Para contactar a un vendedor en Lyrium: 1. Ingresa a la página del producto o tienda que te interesa. 2. Busca el botón "Contactar" o "Chat con vendedor". 3. Escribe tu consulta y el vendedor te responderá directamente desde su panel. Este sistema de mensajería te permite comunicarte de forma segura sin compartir datos personales. También puedes encontrar los datos de contacto de la tienda en su perfil público.',
        ],
        'solicitar_comprobante' => [
            'keywords' => ['solicitar comprobante', 'factura', 'boleta', 'comprobante electronico', 'recibo', 'facturacion'],
            'terms'    => ['comprobante', 'factura', 'boleta', 'recibo', 'facturacion'],
            'response' => 'Lyrium emite comprobantes electrónicos (factura o boleta) con validación SUNAT para todas las compras. Durante el proceso de pago puedes seleccionar si deseas boleta o factura. Si necesitas un comprobante adicional o tienes problemas con tu factura electrónica, ingresa a tu panel de usuario, ve a la sección "Mis pedidos", selecciona el pedido y elige la opción "Solicitar comprobante". También puedes contactar al vendedor directamente si requieres algún ajuste en los datos de facturación.',
        ],
        'metodos_pago' => [
            'keywords' => ['metodos de pago', 'como pagar', 'formas de pago', 'pago disponible', 'deposito', 'transferencia'],
            'terms'    => ['pago', 'pagar', 'tarjeta', 'transferencia', 'deposito', 'yape', 'plin'],
            'response' => 'Lyrium acepta los siguientes métodos de pago: 1. Tarjetas de crédito y débito (Visa, MasterCard, American Express). 2. Transferencia bancaria y depósito en cuenta. 3. Yape y Plin (billeteras digitales peruanas). 4. Pago contraentrega (disponible en zonas seleccionadas). Todos los pagos con tarjeta se procesan de forma segura a través de nuestra pasarela de pago. Al momento de pagar podrás seleccionar el método que prefieras.',
        ],
        'registrar_tienda' => [
            'keywords' => ['registrar tienda', 'crear tienda', 'abrir tienda', 'registrarse como vendedor'],
            'terms'    => ['tienda', 'registrar', 'crear tienda'],
            'response' => 'Para registrar tu tienda en Lyrium: 1. Crea una cuenta como usuario en Lyrium. 2. Ingresa a tu panel de usuario y selecciona "Registrar tienda". 3. Completa el formulario con los datos de tu negocio (nombre, descripción, categoría, dirección, logo y banner). 4. Revisa y acepta los términos y condiciones. 5. Envía tu solicitud. Un equipo de Lyrium revisará tu solicitud y te notificará cuando tu tienda sea aprobada. El proceso de aprobación generalmente toma entre 1 y 3 días hábiles.',
        ],
        'estado_pedido' => [
            'keywords' => ['estado de pedido', 'seguimiento pedido', 'donde esta mi pedido', 'cuando llega', 'tiempo de entrega'],
            'terms'    => ['pedido', 'envio', 'entrega', 'seguimiento', 'estado'],
            'response' => 'Para consultar el estado de tu pedido: 1. Inicia sesión en tu cuenta de Lyrium. 2. Ve a tu panel de usuario y haz clic en "Mis pedidos". 3. Allí verás el estado actual de cada pedido (pendiente, confirmado, en preparación, enviado, entregado). 4. Haz clic en un pedido para ver más detalles, incluyendo el número de seguimiento si aplica. Si tu pedido incluye envío, recibirás notificaciones por correo electrónico con las actualizaciones del estado.',
        ],
        'devoluciones' => [
            'keywords' => ['politica devolucion', 'como devolver', 'cambio producto', 'reembolso', 'garantia', 'devolver producto'],
            'terms'    => ['devolucion', 'reembolso', 'cambio', 'garantia', 'devolver'],
            'response' => 'Lyrium tiene una política de devolución que protege a los compradores: 1. Tienes hasta 7 días calendario desde la recepción del producto para solicitar una devolución. 2. El producto debe estar en su estado original, sin uso y con el empaque completo. 3. Para iniciar una devolución, ingresa a "Mis pedidos", selecciona el producto y haz clic en "Solicitar devolución". 4. Describe el motivo y adjunta evidencia si es necesario. 5. El vendedor revisará tu solicitud y coordinará la logística de devolución. Los reembolsos se procesan una vez recibido y verificado el producto.',
        ],
        'soporte' => [
            'keywords' => ['contactar soporte', 'ayuda', 'soporte tecnico', 'reportar problema', 'asistencia', 'servicio al cliente', 'whatsapp', 'escribir whatsapp', 'numero whatsapp', 'contactar whatsapp', 'hablar whatsapp', 'whatsapp lyrium'],
            'terms'    => ['soporte', 'ayuda', 'asistencia', 'problema', 'reportar', 'contactar', 'whatsapp'],
            'response' => 'Si necesitas ayuda adicional, puedes contactarnos por los siguientes canales: 1. WhatsApp: 51937093420 (horario de atención: lun-vie 9:00 a 18:00). 2. Chat en línea de Lyrium — haz clic en el ícono de chat en la esquina inferior derecha para recibir ayuda al instante. 3. Correo electrónico: soporte@lyrium.pe. 4. Redes sociales: Instagram, Facebook y TikTok como @lyrium_biomarketplace. 5. También puedes generar un ticket de soporte desde tu panel de usuario en la sección "Soporte".',
        ],
        'comisiones' => [
            'keywords' => ['comisiones', 'cuanto cobra lyrium', 'tarifas vendedor', 'porcentaje comision', 'costo vender'],
            'terms'    => ['comision', 'tarifa', 'costo', 'cobro', 'porcentaje'],
            'audience' => ['seller', 'administrator'],
            'response' => 'Lyrium ofrece planes de suscripción con diferentes porcentajes de comisión: 1. Plan Emprende: 5% de comisión por venta. 2. Plan Crece: 10% de comisión por venta. 3. Plan Especial: 15% de comisión por venta. Cada plan incluye beneficios adicionales como mayor visibilidad, herramientas de gestión y soporte prioritario. Puedes consultar los detalles de cada plan en la sección de Planes de nuestra web o desde tu panel de vendedor.',
        ],
        'registro_usuario' => [
            'keywords' => ['como registrarse', 'crear cuenta', 'registrarse en lyrium', 'crear perfil', 'darse de alta'],
            'terms'    => ['registro', 'registrarse', 'cuenta', 'crear cuenta', 'usuario'],
            'response' => 'Para registrarte en Lyrium: 1. Haz clic en "Iniciar sesión" en la parte superior derecha. 2. Selecciona "Crear cuenta" o "Registrarse". 3. Completa tus datos personales (nombres, correo electrónico, contraseña). 4. Acepta los términos y condiciones. 5. Recibirás un código de verificación por correo electrónico. 6. Ingresa el código para verificar tu cuenta. ¡Listo! Ya puedes explorar y comprar productos en Lyrium.',
        ],
        'pregunta_general' => [
            'keywords' => ['que es lyrium', 'que hacen', 'servicios lyrium', 'informacion', 'acerca de'],
            'terms'    => ['lyrium', 'biomarketplace', 'plataforma', 'informacion'],
            'response' => 'Lyrium es un Biomarketplace peruano que conecta compradores con vendedores de productos y servicios especializados en bienestar, salud natural y estilo de vida sostenible. Ofrecemos una plataforma segura para comprar y vender, con sistemas de pago integrados, seguimiento de pedidos, facturación electrónica y soporte al cliente. Nuestra misión es facilitar el acceso a productos que promuevan una vida saludable mientras apoyamos a emprendedores y pequeñas empresas del sector.',
        ],

        // ── MIS PEDIDOS ──────────────────────────────────────────────────────────
        'cancelar_pedido' => [
            'keywords' => ['cancelar pedido', 'anular pedido', 'cancel pedido', 'quiero cancelar mi pedido', 'como cancelo'],
            'terms'    => ['cancelar', 'anular', 'cancel'],
            'response' => "Para cancelar un pedido: ve a tu panel → 'Mis pedidos' → selecciona el pedido → 'Cancelar pedido'. ⚠️ Solo puedes cancelar pedidos en estado *Pendiente*. Si el vendedor ya confirmó el pedido, comunícate directamente con él o abre un ticket de soporte desde tu panel. 🔄 _Escribe \"soporte\" si necesitas ayuda adicional._",
        ],
        'modificar_pedido' => [
            'keywords' => ['modificar pedido', 'cambiar pedido', 'editar pedido', 'cambiar direccion envio', 'cambiar direccion de entrega'],
            'terms'    => ['modificar', 'cambiar pedido', 'editar pedido', 'direccion'],
            'response' => "Una vez realizado, no es posible modificar productos ni cantidades en un pedido. Si el pedido aún está en estado *Pendiente*, puedes cancelarlo y volver a comprarlo con los cambios que necesitas. Para cambiar la dirección de entrega antes del despacho, escríbenos por WhatsApp: https://wa.me/51937093420 🛍️",
        ],
        'historial_compras' => [
            'keywords' => ['historial de compras', 'mis compras', 'compras anteriores', 'ver mis pedidos', 'lista de pedidos'],
            'terms'    => ['historial', 'compras anteriores', 'mis pedidos'],
            'response' => "Puedes ver todas tus compras en tu panel de usuario → 'Mis pedidos'. Allí encontrarás el historial completo con fecha, monto y estado de cada pedido. Haz clic en cualquier pedido para ver sus detalles. 📦",
        ],
        'disputa_pedido' => [
            'keywords' => ['abrir disputa', 'reclamar pedido', 'problema con pedido', 'pedido incorrecto', 'pedido danado', 'pedido dañado', 'no llego mi pedido', 'no llego pedido'],
            'terms'    => ['disputa', 'reclamo', 'reclamar', 'incorrecto', 'danado'],
            'response' => "Si tienes un problema con tu pedido (no llegó, llegó dañado o incorrecto), puedes abrir una disputa: ve a 'Mis pedidos' → selecciona el pedido → 'Abrir disputa'. Describe el problema, adjunta fotos si es posible y nuestro equipo mediará entre tú y el vendedor. Tenemos hasta 7 días hábiles para resolverlo. 🛡️",
        ],

        // ── ESTADO DEL PEDIDO ─────────────────────────────────────────────────────
        'significado_estados' => [
            'keywords' => ['que significa pendiente', 'que significa confirmado', 'que significa enviado', 'que significa en preparacion', 'estados del pedido'],
            'terms'    => ['significa', 'estado pedido', 'pendiente', 'confirmado', 'preparacion'],
            'response' => "Los estados de tu pedido significan:\n🟡 *Pendiente* — esperando confirmación del vendedor.\n🔵 *Confirmado* — el vendedor aceptó tu pedido y el pago fue procesado.\n🟠 *En preparación* — el vendedor está alistando tu pedido.\n🚚 *Enviado* — tu pedido está en camino (recibirás el número de seguimiento).\n✅ *Entregado* — pedido recibido exitosamente.",
        ],
        'rastreo_envio' => [
            'keywords' => ['rastrear envio', 'numero de seguimiento', 'tracking pedido', 'donde va mi paquete', 'seguir mi paquete'],
            'terms'    => ['rastrear', 'tracking', 'seguimiento', 'numero envio'],
            'response' => "Cuando el vendedor despache tu pedido, recibirás una notificación con el número de seguimiento. Para verlo: ve a 'Mis pedidos' → selecciona el pedido → encontrarás el número de tracking y el enlace a la empresa de transporte. 📦🚚",
        ],
        'tiempo_entrega' => [
            'keywords' => ['cuanto tiempo demora', 'cuanto tarda', 'dias de entrega', 'tiempo de envio', 'cuando me llega'],
            'terms'    => ['demora', 'tarda', 'dias entrega', 'tiempo envio', 'llega'],
            'response' => "El tiempo de entrega varía según la tienda y tu ubicación:\n📍 *Lima Metropolitana:* 1–3 días hábiles (generalmente).\n📦 *Provincias:* 3–7 días hábiles.\nCada tienda indica sus tiempos estimados en su perfil. También puedes verlo antes de confirmar tu compra en el resumen del carrito. ⏱️",
        ],

        // ── PAGOS ─────────────────────────────────────────────────────────────────
        'pago_confirmado' => [
            'keywords' => ['mi pago fue confirmado', 'confirmar pago', 'se proceso mi pago', 'pago exitoso', 'pago aprobado'],
            'terms'    => ['pago confirmado', 'pago exitoso', 'pago aprobado', 'proceso pago'],
            'response' => "Si tu pago fue procesado correctamente, el estado de tu pedido cambiará a *Confirmado* y recibirás un correo de confirmación. Puedes verificarlo en 'Mis pedidos'. Si el estado sigue en *Pendiente* después de 15 minutos, escríbenos por WhatsApp: https://wa.me/51937093420 💳✅",
        ],
        'pago_rechazado' => [
            'keywords' => ['pago rechazado', 'pago fallido', 'no se proceso el pago', 'error en el pago', 'pago no completado'],
            'terms'    => ['rechazado', 'fallido', 'error pago', 'no proceso'],
            'response' => "Los pagos pueden ser rechazados por:\n• Fondos insuficientes en la tarjeta.\n• Datos de tarjeta incorrectos.\n• Bloqueo de seguridad de tu banco.\n\n✅ *¿Qué hacer?* Verifica tus datos, intenta con otro método de pago, o contacta a tu banco. Si el problema persiste, escríbenos: https://wa.me/51937093420 💳",
        ],
        'pago_con_lirios' => [
            'keywords' => ['pagar con lirios', 'usar lirios en pago', 'aplicar lirios', 'lirios en checkout', 'descuento con lirios'],
            'terms'    => ['pagar lirios', 'usar lirios', 'aplicar lirios'],
            'response' => "Puedes usar tus puntos Lirios como descuento al pagar. En el paso de pago del checkout verás la opción 'Pagar con Lirios' si tienes saldo disponible y la tienda lo permite. Cada tienda define el porcentaje máximo que puedes pagar con Lirios 🌿💚",
        ],

        // ── ENVÍOS ────────────────────────────────────────────────────────────────
        'costo_envio' => [
            'keywords' => ['cuanto cuesta el envio', 'precio de envio', 'tarifa de envio', 'costo de despacho', 'cuanto es el flete'],
            'terms'    => ['costo envio', 'precio envio', 'tarifa envio', 'flete'],
            'response' => "El costo de envío varía según la tienda y tu zona de entrega. Puedes verlo:\n1. En el perfil de cada tienda (tarifas referenciales).\n2. En el resumen de tu carrito antes de pagar.\n\nAlgunas tiendas ofrecen *recojo en tienda* sin costo adicional. 🛵",
        ],
        'envio_gratis' => [
            'keywords' => ['envio gratis', 'envio gratuito', 'sin costo de envio', 'despacho gratis', 'no cobran envio'],
            'terms'    => ['envio gratis', 'envio gratuito', 'gratis envio'],
            'response' => "Algunas tiendas ofrecen envío gratuito cuando superas un monto mínimo de compra. Revisa las condiciones en el perfil de la tienda o en la descripción del producto. También puedes buscar tiendas con la etiqueta 'Envío gratis' en la plataforma. 🎁🚚",
        ],
        'recojo_tienda' => [
            'keywords' => ['recoger en tienda', 'recojo en tienda', 'retirar en tienda', 'pickup'],
            'terms'    => ['recoger', 'recojo', 'retirar', 'pickup'],
            'response' => "Sí, muchas tiendas ofrecen la opción de recojo en tienda sin costo de envío. Durante el checkout, si el vendedor lo habilita, verás la opción 'Recoger en tienda' con la dirección de la sucursal y el horario de atención. 🏪",
        ],
        'problema_entrega' => [
            'keywords' => ['no llego el envio', 'paquete no entregado', 'no estaba en casa entrega', 'segundo intento entrega', 'fallo la entrega'],
            'terms'    => ['no llego envio', 'paquete no llego', 'entrega fallida'],
            'response' => "Si hubo un problema con la entrega, el transportista generalmente realiza hasta 2 intentos. Revisa si dejaron un aviso de visita. Si no recibes noticias en 24 horas, escribe al vendedor desde 'Mis pedidos' → 'Contactar vendedor', o abre un ticket de soporte desde tu panel. 📦❓",
        ],

        // ── EMPAQUE Y CUIDADO DEL PEDIDO ─────────────────────────────────────────
        'empaque_cuidado' => [
            'keywords' => ['como empacan', 'cuidado del empaque', 'como cuidan mi pedido', 'empaque seguro', 'como protegen el producto'],
            'terms'    => ['empaque', 'empacar', 'embalaje'],
            'response' => "En Lyrium el empaque es parte de nuestro compromiso con tu salud 🌿. Cada pedido se protege según el tipo de producto (frágil, líquido, perecible o delicado) para que llegue en perfecto estado. Además, fotografiamos cada pedido antes de cerrarlo como respaldo ante cualquier reclamo. 📦💚",
        ],
        'simbolos_caja' => [
            'keywords' => ['que significan los simbolos', 'simbolos de la caja', 'que significa fragil en la caja', 'iconos de la caja', 'dibujos en la caja'],
            'terms'    => ['simbolo caja', 'icono caja'],
            'response' => "Los símbolos en tu caja te indican cómo manejarla:\n📦 *Frágil* (copa rota) — contenido delicado, manejo cuidadoso.\n⬆️ *Este lado arriba* (flechas) — no debe voltearse durante el viaje.\n☔ *Mantener seco* (paraguas) — evitar humedad.\nSon estándares internacionales para proteger tu pedido en el camino. 🌱",
        ],
        'evidencia_empaque' => [
            'keywords' => ['prueba de que llego bien empacado', 'foto del pedido antes de enviar', 'tienen foto de mi pedido', 'como saben si llego dañado', 'como saben si llego danado'],
            'terms'    => ['foto del pedido', 'evidencia empaque'],
            'response' => "Antes de cerrar cada pedido, tomamos una foto del contenido ya embalado. Esto nos permite verificar el estado real con el que salió tu pedido si necesitas abrir una disputa por daños o faltantes. 📸🛡️ _Escribe \"disputa\" si necesitas reportar un problema con tu pedido._",
        ],

        // ── MI CUENTA ─────────────────────────────────────────────────────────────
        'recuperar_contrasena' => [
            'keywords' => ['olvide mi contrasena', 'recuperar contrasena', 'no recuerdo mi clave', 'restablecer contrasena', 'cambiar clave olvidada'],
            'terms'    => ['olvide contrasena', 'recuperar contrasena', 'restablecer clave', 'clave olvidada'],
            'response' => "Para recuperar tu contraseña: ve a la pantalla de inicio de sesión → haz clic en '¿Olvidaste tu contraseña?' → ingresa tu correo electrónico → recibirás un enlace de recuperación. Revisa también tu carpeta de spam si no lo ves en unos minutos. 🔑",
        ],
        'cambiar_contrasena' => [
            'keywords' => ['cambiar contrasena', 'actualizar contrasena', 'nueva contrasena', 'modificar clave'],
            'terms'    => ['cambiar contrasena', 'actualizar contrasena', 'nueva clave'],
            'response' => "Para cambiar tu contraseña: ve a tu perfil de usuario → 'Configuración de cuenta' → 'Cambiar contraseña'. Ingresa tu contraseña actual y luego la nueva. Asegúrate de usar una contraseña segura (mínimo 8 caracteres, con números y letras). 🔐",
        ],
        'actualizar_perfil' => [
            'keywords' => ['actualizar perfil', 'editar perfil', 'cambiar datos personales', 'cambiar nombre', 'cambiar telefono', 'cambiar foto'],
            'terms'    => ['actualizar perfil', 'editar perfil', 'datos personales'],
            'response' => "Para actualizar tus datos personales: ingresa a tu perfil → 'Editar perfil'. Puedes modificar tu nombre, teléfono, dirección y foto de perfil. Los cambios se guardan de inmediato. 👤✏️",
        ],
        'verificar_correo' => [
            'keywords' => ['verificar correo', 'codigo de verificacion', 'no recibi el codigo', 'reenviar codigo', 'verificar cuenta'],
            'terms'    => ['verificar correo', 'codigo verificacion', 'reenviar codigo'],
            'response' => "Al registrarte, te enviamos un código OTP a tu correo para verificar tu cuenta. Si no lo recibiste:\n1. Revisa la carpeta de spam o no deseados.\n2. En la pantalla de verificación, haz clic en 'Reenviar código'.\n\nSi el problema persiste, escríbenos por WhatsApp: https://wa.me/51937093420 📧",
        ],
        'cuenta_google' => [
            'keywords' => ['iniciar sesion con google', 'login google', 'registrarse con google', 'vincular google', 'cuenta google'],
            'terms'    => ['google', 'oauth', 'iniciar google'],
            'response' => "Puedes iniciar sesión en Lyrium con tu cuenta de Google. En la pantalla de login, haz clic en 'Continuar con Google' y autoriza el acceso. Es rápido, seguro y no necesitas crear una contraseña adicional. 🔵",
        ],
        'seguridad_cuenta' => [
            'keywords' => ['me hackearon la cuenta', 'acceso no autorizado', 'alguien entro a mi cuenta', 'cuenta comprometida', 'cuenta robada'],
            'terms'    => ['hackearon', 'acceso no autorizado', 'cuenta comprometida', 'robo cuenta'],
            'response' => "⚠️ Si crees que alguien accedió a tu cuenta sin permiso:\n1. Cambia tu contraseña de inmediato desde 'Configuración de cuenta'.\n2. Revisa los pedidos recientes en busca de actividad desconocida.\n3. Contáctanos urgentemente por WhatsApp: https://wa.me/51937093420\n\nNuestro equipo bloqueará el acceso no autorizado y te ayudará a recuperar tu cuenta. 🛡️",
        ],
        'eliminar_cuenta' => [
            'keywords' => ['eliminar cuenta', 'borrar cuenta', 'dar de baja', 'cerrar cuenta lyrium'],
            'terms'    => ['eliminar cuenta', 'borrar cuenta', 'dar de baja'],
            'response' => "Para eliminar tu cuenta, escríbenos por WhatsApp: https://wa.me/51937093420 o abre un ticket de soporte desde tu panel. Por seguridad, verificaremos tu identidad antes de proceder. Ten en cuenta que esta acción es irreversible y perderás tu historial de pedidos y puntos Lirios. 🗑️",
        ],

        // ── PROGRAMA DE PUNTOS LIRIOS ─────────────────────────────────────────────
        'lirios_saldo' => [
            'keywords' => [
                'cuantos lirios tengo', 'saldo de lirios', 'mis puntos lirios', 'ver lirios', 'monedero lirios',
                'mis lirios no aparecieron', 'mis lirios no aparecen', 'no me llegaron los lirios',
                'no se acreditaron mis lirios', 'no se acreditaron lirios', 'no se agregaron mis lirios',
                'lirios no se acreditaron', 'lirios no aparecen', 'lirios no llegaron',
                'no recibi mis lirios', 'no tengo mis lirios', 'donde estan mis lirios',
                'perdí mis lirios', 'perdi mis lirios', 'se perdieron mis lirios',
                'mis lirios desaparecieron', 'no veo mis lirios',
            ],
            'terms'    => ['lirios', 'puntos lirios', 'saldo lirios', 'monedero'],
            'response' => "Si tus puntos Lirios no aparecieron después de una compra, prueba esto:\n1. Ve a tu panel → sección '🌸 Lirios' y revisa el historial de transacciones.\n2. Verifica que la tienda donde compraste participa en el programa Lirios.\n3. Espera hasta 24 horas, ya que la acreditación puede tomar un momento.\n\nSi después de 24 horas siguen sin aparecer, escríbenos por WhatsApp: https://wa.me/51937093420 y lo revisamos. 💚🌿",
        ],
        'lirios_ganar' => [
            'keywords' => [
                'como gano lirios', 'ganar puntos lirios', 'acumular lirios', 'como funcionan los lirios',
                'como se ganan lirios', 'como se ganan los lirios', 'como acumulo lirios',
                'para que sirven los lirios', 'que son los lirios', 'que son lirios',
                'como obtengo lirios', 'como consigo lirios',
            ],
            'terms'    => ['ganar lirios', 'acumular lirios', 'como lirios', 'para que lirios'],
            'response' => "Ganas puntos Lirios al realizar compras en las tiendas participantes de Lyrium Biomarketplace. El monto acreditado puede variar según la tienda y el producto. También puedes ganar Lirios en promociones especiales dentro de la plataforma. ¡Entre más compras, más Lirios acumulas! 🌱✨",
        ],
        'lirios_canjear' => [
            'keywords' => [
                'como canjeo lirios', 'canjear lirios', 'usar lirios', 'como uso mis lirios', 'redimir lirios',
                'como aplico mis lirios', 'aplicar lirios al pago', 'pagar con mis lirios',
                'quiero usar mis lirios', 'quiero canjear mis lirios', 'no me deja usar lirios',
                'no puedo usar lirios', 'no me aparece opcion lirios', 'no veo la opcion de lirios',
            ],
            'terms'    => ['canjear lirios', 'usar lirios', 'redimir lirios', 'aplicar lirios'],
            'response' => "Para canjear tus Lirios:\n1. Agrega productos al carrito y ve al checkout.\n2. Si tienes saldo disponible y la tienda lo permite, verás la opción 'Aplicar Lirios'.\n3. El descuento se aplica automáticamente al total.\n\n💡 Si no ves la opción, es posible que la tienda no participe en el programa Lirios o hayas alcanzado el porcentaje máximo permitido. 🌿",
        ],
        'lirios_vencimiento' => [
            'keywords' => [
                'lirios vencen', 'expiran los lirios', 'caducidad lirios', 'fecha vencimiento lirios',
                'hasta cuando puedo usar lirios', 'lirios tienen fecha', 'vencimiento de lirios',
                'cuanto tiempo tengo para usar lirios',
            ],
            'terms'    => ['vencen lirios', 'expiran lirios', 'caducidad lirios', 'vencimiento lirios'],
            'response' => "Actualmente los puntos Lirios no tienen fecha de vencimiento. Podrás usarlos cuando quieras. Te notificaremos con anticipación si esta política cambia en el futuro. 🌿♾️",
        ],
        'lirios_transferir' => [
            'keywords' => [
                'transferir lirios', 'pasar lirios a otro', 'compartir lirios', 'enviar lirios a amigo',
                'regalar lirios', 'puedo dar lirios', 'puedo compartir lirios',
                'lirios a otra persona', 'lirios a otra cuenta',
            ],
            'terms'    => ['transferir lirios', 'pasar lirios', 'compartir lirios', 'regalar lirios'],
            'response' => "Por el momento, los puntos Lirios no son transferibles entre cuentas. Solo el titular de la cuenta puede usarlos en sus compras. 🌿",
        ],

        // ── PROMOCIONES ───────────────────────────────────────────────────────────
        'promociones_disponibles' => [
            'keywords' => ['que promociones hay', 'ofertas disponibles', 'descuentos lyrium', 'ver promociones', 'hay ofertas'],
            'terms'    => ['promociones', 'ofertas', 'descuentos'],
            'response' => "Las promociones y ofertas activas las puedes encontrar en:\n🏠 La página principal de Lyrium (sección 'Ofertas').\n🏪 El perfil de cada tienda.\n📧 Newsletter de Lyrium (suscríbete para recibir ofertas exclusivas).\n\nCada vendedor gestiona sus propios descuentos y promociones. ¡Revísalas antes de comprar! 🎉",
        ],
        'cupon_descuento' => [
            'keywords' => ['como uso un cupon', 'codigo descuento', 'ingresar cupon', 'aplicar cupon', 'tengo un cupon'],
            'terms'    => ['cupon', 'codigo descuento', 'codigo promo'],
            'response' => "Para usar un cupón de descuento: en el paso de pago del checkout, busca el campo 'Código de descuento' o 'Cupón', ingresa tu código y haz clic en 'Aplicar'. El descuento se reflejará automáticamente en el total si el cupón es válido y aplica a los productos de tu carrito. 🎟️",
        ],

        // ── SOPORTE ───────────────────────────────────────────────────────────────
        'abrir_ticket' => [
            'keywords' => ['abrir ticket', 'crear ticket', 'ticket de soporte', 'solicitar ayuda soporte', 'como pido ayuda'],
            'terms'    => ['ticket', 'crear ticket', 'abrir ticket'],
            'response' => "Para abrir un ticket de soporte: ve a tu panel de usuario → 'Soporte' → 'Nuevo ticket'. Describe tu problema con el mayor detalle posible y adjunta capturas si aplica. Recibirás notificaciones sobre el estado de tu ticket. ⏱️ Respondemos en un máximo de 1 día hábil.",
        ],
        'horario_soporte' => [
            'keywords' => ['horario de atencion', 'cuando atienden', 'horario soporte', 'dias de atencion'],
            'terms'    => ['horario', 'atencion', 'dias atienden'],
            'response' => "Nuestro equipo de soporte atiende:\n🕘 Lunes a viernes: 9:00 a 18:00 (hora Perú)\n📅 Sábados: 9:00 a 13:00\n\nFuera de ese horario puedes abrir un ticket o escribir por WhatsApp: https://wa.me/51937093420 y te responderemos al siguiente día hábil. 🌿",
        ],
        'reportar_vendedor' => [
            'keywords' => ['reportar vendedor', 'denunciar tienda', 'reportar tienda', 'queja de vendedor'],
            'terms'    => ['reportar vendedor', 'denunciar tienda', 'queja vendedor'],
            'response' => "Para reportar un vendedor o tienda: ve al perfil de la tienda → haz clic en 'Reportar'. También puedes abrir un ticket de soporte desde tu panel → 'Soporte' → 'Nuevo ticket', indicando el nombre de la tienda y el motivo del reporte. Todos los reportes son revisados por nuestro equipo. 🚨",
        ],
        'dejar_resena' => [
            'keywords' => ['dejar resena', 'calificar producto', 'poner resena', 'dar opinion', 'evaluar compra', 'dejar calificacion'],
            'terms'    => ['resena', 'calificar', 'opinion producto', 'evaluar'],
            'response' => "Para dejar una reseña: ve a 'Mis pedidos' → selecciona el pedido entregado → haz clic en 'Dejar reseña'. Puedes calificar con estrellas y escribir tu opinión. Solo los compradores que recibieron el producto pueden dejar reseñas para garantizar su autenticidad. ⭐",
        ],

        // ── USO DE LA PLATAFORMA ──────────────────────────────────────────────────
        'buscar_producto' => [
            'keywords' => ['como busco un producto', 'buscar producto', 'encontrar producto', 'buscar tienda', 'como encuentro'],
            'terms'    => ['buscar producto', 'buscar tienda', 'encontrar producto'],
            'response' => "Usa la barra de búsqueda en la parte superior de la plataforma para buscar productos o tiendas por nombre. También puedes navegar por las categorías del menú principal para explorar por tipo de producto (suplementos, alimentos, servicios de salud, etc.). 🔍🌱",
        ],
        'agregar_carrito' => [
            'keywords' => ['agregar al carrito', 'anadir carrito', 'como compro varios productos', 'carrito de compras'],
            'terms'    => ['carrito', 'agregar carrito', 'anadir carrito'],
            'response' => "Para agregar un producto al carrito: abre la página del producto → selecciona la cantidad que deseas → haz clic en 'Agregar al carrito'. Puedes seguir comprando y agregar productos de diferentes tiendas. Al finalizar, revisa tu carrito y procede al pago. 🛒",
        ],
        'seguridad_datos' => [
            'keywords' => ['son seguros mis datos', 'privacidad de datos', 'que hacen con mis datos', 'proteccion de datos', 'datos personales seguros'],
            'terms'    => ['datos seguros', 'privacidad datos', 'proteccion datos'],
            'response' => "En Lyrium protegemos tu información según la Ley de Protección de Datos Personales del Perú (Ley N° 29733). Tus datos de pago se procesan en pasarelas certificadas (Izipay/Culqi) y nunca los almacenamos directamente. No compartimos tu información personal con terceros sin tu consentimiento. 🔒🌿",
        ],
        'notificaciones' => [
            'keywords' => ['activar notificaciones', 'configurar notificaciones', 'no recibo notificaciones', 'notificaciones de pedidos'],
            'terms'    => ['notificaciones', 'activar notif', 'configurar notif'],
            'response' => "Para gestionar tus notificaciones: ve a tu perfil → 'Configuración' → 'Notificaciones'. Puedes activar o desactivar notificaciones push (en tu navegador) y por correo electrónico para pedidos, mensajes y promociones. Asegúrate de permitir las notificaciones en tu navegador la primera vez que te lo solicite. 🔔",
        ],
        'agendar_servicio' => [
            'keywords' => ['como agendo un servicio', 'reservar servicio', 'agendar cita', 'comprar servicio', 'reservar cita'],
            'terms'    => ['agendar servicio', 'reservar servicio', 'agendar cita', 'cita servicio'],
            'response' => "Para adquirir un servicio en Lyrium: busca el servicio que te interesa → haz clic en 'Reservar' → completa el pago. Una vez confirmado, la tienda se pondrá en contacto contigo para coordinar el horario y el especialista asignado según tu conveniencia. 🩺📅",
        ],
        'cancelar_servicio' => [
            'keywords' => ['cancelar cita', 'cancelar servicio', 'anular reserva servicio', 'no puedo ir a cita'],
            'terms'    => ['cancelar cita', 'cancelar servicio', 'anular reserva'],
            'response' => "Para cancelar una reserva de servicio, comunícate directamente con la tienda a través de la plataforma (ve a 'Mis pedidos' → selecciona la reserva → 'Contactar tienda'). Cada tienda tiene su propia política de cancelación. Si tienes problemas, abre un ticket de soporte. 📅❌",
        ],
        'app_movil' => [
            'keywords' => ['tienen app', 'app movil lyrium', 'descargar app', 'lyrium en celular', 'aplicacion lyrium'],
            'terms'    => ['app movil', 'aplicacion', 'descargar app'],
            'response' => "Por el momento Lyrium funciona como una plataforma web completamente optimizada para móviles. Puedes acceder desde cualquier navegador en tu celular sin necesidad de descargar nada. Próximamente lanzaremos nuestra app nativa. 📱🌱",
        ],
    ];

    public function isHandoffResponse(string $response): bool
    {
        return $response === self::ASESOR_RESPONSE;
    }

    /**
     * @param array<int, array{role: string, content: string}> $history
     */
    public function find(string $message, array $history = [], ?string $role = null): ?string
    {
        $normalized = $this->normalize($message);

        // 1. Lyrio menu intents — whole-word match, keywords normalized before compare
        foreach (self::LILY_INTENTS as $intent) {
            foreach ($intent['keywords'] as $keyword) {
                if ($this->matchesWholeWord($normalized, $keyword)) {
                    return $intent['response'];
                }
            }
        }

        // 2. Context-aware single-char/digit options (A-F, 1-9)
        //    Only activates when the previous bot message was a menu
        if (strlen($normalized) <= 2 && $normalized !== '') {
            $lastBot = $this->getLastBotMessage($history);
            if ($lastBot !== null) {
                if ($this->isVenderMenuContext($lastBot) && isset(self::VENDER_OPTIONS[$normalized])) {
                    return self::VENDER_OPTIONS[$normalized];
                }
                if ($this->isComprarMenuContext($lastBot) && isset(self::COMPRAR_OPTIONS[$normalized])) {
                    return self::COMPRAR_OPTIONS[$normalized];
                }
            }
        }

        // 3. Static FAQs — falls through to Gemini if nothing matches
        return $this->findInFaqs($normalized, $role);
    }

    private function matchesWholeWord(string $text, string $keyword): bool
    {
        // Normalize the keyword too so accented variants match (e.g. "menú" → "menu")
        $normalizedKeyword = $this->normalize($keyword);
        $escaped = preg_quote($normalizedKeyword, '/');
        return (bool) preg_match('/(?<![a-z0-9])' . $escaped . '(?![a-z0-9])/', $text);
    }

    /**
     * @param array<int, array{role: string, content: string}> $history
     */
    private function getLastBotMessage(array $history): ?string
    {
        for ($i = count($history) - 1; $i >= 0; $i--) {
            if (($history[$i]['role'] ?? '') === 'assistant') {
                return $this->normalize($history[$i]['content'] ?? '');
            }
        }

        return null;
    }

    private function isVenderMenuContext(string $lastBotNormalized): bool
    {
        // Frase única del menú de vender
        return str_contains($lastBotNormalized, 'selecciona una opcion escribiendo la letra');
    }

    private function isComprarMenuContext(string $lastBotNormalized): bool
    {
        // Frase única del menú de comprar
        return str_contains($lastBotNormalized, 'quisiera saber como funcionan los envios');
    }

    private function findInFaqs(string $normalized, ?string $role = null): ?string
    {
        $bestMatch = null;
        $bestScore = 0;

        foreach (self::FAQS as $faq) {
            if (isset($faq['audience']) && ($role === null || !in_array($role, $faq['audience'], true))) {
                continue;
            }

            $score = $this->matchScore($normalized, $faq);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $faq;
            }
        }

        if ($bestMatch !== null && $bestScore >= self::SIMILARITY_THRESHOLD) {
            return $bestMatch['response'];
        }

        return null;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        $replacements = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
        ];
        $text = strtr($text, $replacements);

        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);

        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function matchScore(string $normalized, array $faq): int
    {
        foreach ($faq['keywords'] as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return 100;
            }
        }

        $score = 0;
        foreach ($faq['terms'] as $term) {
            if (str_contains($normalized, $term)) {
                $score += 25;
            }
        }

        return min($score, 99);
    }
}
