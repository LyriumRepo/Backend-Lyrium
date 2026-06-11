<?php

declare(strict_types=1);

namespace App\Services;

final class ChatBotFaqService
{
    private const SIMILARITY_THRESHOLD = 60;

    private const FAQS = [
        'comprar_producto' => [
            'keywords' => ['como compro', 'como adquirir', 'quiero comprar', 'comprar producto', 'realizar pedido', 'hacer un pedido', 'como pedir'],
            'terms'    => ['compra', 'comprar', 'adquirir', 'pedir', 'orden', 'producto'],
            'response' => 'Para comprar un producto en Lyrium: 1. Navega por las categorías o usa el buscador para encontrar el producto que deseas. 2. Haz clic en el producto para ver los detalles. 3. Selecciona la cantidad y añádelo al carrito. 4. Revisa tu carrito y haz clic en "Ir a pagar". 5. Completa tus datos de envío, selecciona un método de pago y confirma la compra. Recibirás una confirmación por correo electrónico y podrás dar seguimiento a tu pedido desde tu panel de usuario.',
        ],
        'contactar_vendedor' => [
            'keywords' => ['contactar vendedor', 'hablar con vendedor', 'comunicarme con vendedor', 'mensaje a vendedor', 'escribir vendedor'],
            'terms'    => ['vendedor', 'contactar', 'comunicar', 'mensaje', 'hablar', 'chat'],
            'response' => 'Para contactar a un vendedor en Lyrium: 1. Ingresa a la página del producto o tienda que te interesa. 2. Busca el botón "Contactar" o "Chat con vendedor". 3. Escribe tu consulta y el vendedor te responderá directamente desde su panel. Este sistema de mensajería te permite comunicarte de forma segura sin compartir datos personales. También puedes encontrar los datos de contacto de la tienda en su perfil público.',
        ],
        'solicitar_comprobante' => [
            'keywords' => ['solicitar comprobante', 'factura', 'boleta', 'comprobante electronico', 'recibo', 'facturacion'],
            'terms'    => ['comprobante', 'factura', 'boleta', 'recibo', 'facturacion'],
            'response' => 'Lyrium emite comprobantes electrónicos (factura o boleta) con validación SUNAT para todas las compras. Durante el proceso de pago puedes seleccionar si deseas boleta o factura. Si necesitas un comprobante adicional o tienes problemas con tu factura electrónica, ingresa a tu panel de usuario, ve a la sección "Mis pedidos", selecciona el pedido y elige la opción "Solicitar comprobante". También puedes contactar al vendedor directamente si requieres algún ajuste en los datos de facturación.',
        ],
        'metodos_pago' => [
            'keywords' => ['metodos de pago', 'como pagar', 'formas de pago', 'pago disponible', 'tarjeta', 'deposito', 'transferencia'],
            'terms'    => ['pago', 'pagar', 'tarjeta', 'transferencia', 'deposito', 'yape', 'plin'],
            'response' => 'Lyrium acepta los siguientes métodos de pago: 1. Tarjetas de crédito y débito (Visa, MasterCard, American Express). 2. Transferencia bancaria y depósito en cuenta. 3. Yape y Plin (billeteras digitales peruanas). 4. Pago contraentrega (disponible en zonas seleccionadas). Todos los pagos con tarjeta se procesan de forma segura a través de nuestra pasarela de pago. Al momento de pagar podrás seleccionar el método que prefieras.',
        ],
        'registrar_tienda' => [
            'keywords' => ['registrar tienda', 'crear tienda', 'vender en lyrium', 'ser vendedor', 'abrir tienda', 'registrarse como vendedor'],
            'terms'    => ['tienda', 'vender', 'vendedor', 'registrar', 'crear tienda'],
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
    ];

    public function find(string $message): ?string
    {
        $normalized = $this->normalize($message);

        $bestMatch = null;
        $bestScore = 0;

        foreach (self::FAQS as $faq) {
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
