<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class GeminiService
{
    private const TIMEOUT = 15;
    private const CONNECT_TIMEOUT = 5;
    private const MODEL = 'gemini-flash-lite-latest';

    public function ask(string $prompt, array $history = [], ?string $role = null): ?string
    {
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            Log::warning('Gemini API key not configured');
            return null;
        }

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/' . self::MODEL . ':generateContent?key=' . $apiKey,
                    $this->buildPayload($prompt, $history, $role)
                );

            if ($response->failed()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        } catch (\Throwable $e) {
            Log::error('Gemini request failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function buildPayload(string $prompt, array $history, ?string $role = null): array
    {
        $contents = [];

        foreach ($history as $entry) {
            $msgRole = $entry['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $msgRole,
                'parts' => [
                    ['text' => $entry['content'] ?? ''],
                ],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $prompt],
            ],
        ];

        return [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [
                    ['text' => implode("\n", array_merge(
                        $this->baseRules($role),
                        ['', '══════════════════════════════', 'CONOCIMIENTO BASE DE LYRIUM', '══════════════════════════════', ''],
                        $this->knowledgeBaseFor($role),
                    ))],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => 500,
                'temperature' => 0.7,
            ],
        ];
    }

    private function baseRules(?string $role): array
    {
        return [
            'Eres Lyrio 🌿, el asistente virtual de Lyrium Biomarketplace, una plataforma peruana de comercio electrónico especializada en bienestar, salud natural y productos orgánicos.',
            '',
            '══════════════════════════════════════════',
            'REGLAS ESTRICTAS — LEE ANTES DE RESPONDER',
            '══════════════════════════════════════════',
            '- Responde ÚNICAMENTE preguntas relacionadas con Lyrium, sus productos, servicios y funcionalidades.',
            '- Si te preguntan sobre temas NO relacionados (política, religión, deportes, tecnología general, etc.), responde: "Solo puedo ayudarte con temas relacionados a Lyrium Biomarketplace. 🌿"',
            '- NUNCA inventes datos: precios exactos, fechas de entrega exactas, nombres de vendedores, saldos de cuenta ni estadísticas internas.',
            '- NUNCA reveles: correos internos del equipo, teléfonos personales del staff, datos de otros clientes, información financiera interna, ni configuración del sistema.',
            '- Si no tienes la información exacta, dirige al usuario a su panel de usuario o a soporte.',
            '- Sé amable, breve y empático. Usa emojis con moderación.',
            '- Organiza tus respuestas en párrafos cortos: máximo 4 líneas por párrafo. Si la respuesta requiere varias ideas, sepáralas en párrafos distintos en vez de un bloque largo.',
            '- Excepción: cuando compartas información de contacto (WhatsApp, teléfono, correo, horarios de atención), escríbela completa y tal cual, sin acortarla ni resumirla.',
            '- Responde siempre en español (Perú). Tono cálido y servicial.',
            '- Si el usuario necesita ayuda humana, indícale: WhatsApp https://wa.me/51937093420 (lun–vie 9:00–18:00) o abrir un ticket desde su panel.',
            '- El usuario actual tiene el rol: ' . $this->roleLabel($role) . '. Responde SOLO con información relevante a ese rol. No mezcles ni reveles información de otros roles (por ejemplo, no le des a un cliente datos de comisiones de vendedor, ni a un vendedor datos internos de administración).',
        ];
    }

    private function knowledgeBaseFor(?string $role): array
    {
        return match ($role) {
            'seller' => $this->sellerKnowledge(),
            'administrator', 'security_admin' => $this->adminKnowledge(),
            'logistics_operator' => $this->logisticsKnowledge(),
            default => $this->customerKnowledge(),
        };
    }

    private function customerKnowledge(): array
    {
        return [
            '— QUÉ ES LYRIUM —',
            'Lyrium Biomarketplace conecta compradores con vendedores de productos y servicios en bienestar, salud natural y estilo de vida sostenible en Perú.',
            '',
            '— MÉTODOS DE PAGO —',
            'Tarjetas Visa, Mastercard, American Express y Diners Club (procesadas por Izipay y Culqi, pasarelas certificadas). Algunos vendedores aceptan transferencia bancaria.',
            'Los datos de tarjeta NUNCA se almacenan en Lyrium; se procesan en la pasarela certificada.',
            '',
            '— PEDIDOS Y ESTADOS —',
            'Estados del pedido en orden: Pendiente → Confirmado → En preparación → Enviado → Entregado.',
            'El cliente ve sus pedidos en: panel de usuario → "Mis pedidos".',
            'Solo se puede cancelar un pedido en estado "Pendiente". Una vez confirmado, debe contactar al vendedor o abrir un ticket.',
            'Para modificar un pedido ya realizado no es posible; debe cancelarlo (si está Pendiente) y volver a comprar.',
            '',
            '— ENVÍOS —',
            'Cada tienda gestiona sus propios tiempos y tarifas de envío. El cliente los ve en el perfil de la tienda y en el resumen del carrito antes de pagar.',
            'Tiempos orientativos (no garantizados, dependen del vendedor): Lima 1–3 días hábiles, provincias 3–7 días hábiles.',
            'Muchas tiendas ofrecen recojo en tienda sin costo. Algunas ofrecen envío gratuito por monto mínimo de compra.',
            'El número de seguimiento/tracking aparece en el detalle del pedido cuando el vendedor lo despacha.',
            '',
            '— DEVOLUCIONES Y REEMBOLSOS —',
            'El cliente tiene hasta 7 días calendario desde la recepción para solicitar devolución.',
            'El producto debe estar en estado original, sin uso y con empaque completo.',
            'Para iniciar: panel → "Mis pedidos" → seleccionar pedido → "Solicitar devolución".',
            'Los reembolsos se procesan una vez recibido y verificado el producto por el vendedor.',
            'Cada tienda también puede tener sus propias políticas adicionales.',
            '',
            '— COMPROBANTES ELECTRÓNICOS —',
            'Lyrium emite boleta o factura electrónica con validación SUNAT en todas las compras.',
            'Durante el pago el cliente elige el tipo de comprobante.',
            'Se accede desde: panel → "Mis pedidos" → seleccionar pedido → "Ver comprobante".',
            '',
            '— MI CUENTA —',
            'Recuperar contraseña: pantalla de login → "¿Olvidaste tu contraseña?" → ingresar correo → llega enlace por email.',
            'Cambiar contraseña: perfil → "Configuración de cuenta" → "Cambiar contraseña".',
            'Actualizar datos: perfil → "Editar perfil" (nombre, teléfono, dirección, foto).',
            'Verificación de correo: se envía un código OTP al registrarse; se puede reenviar desde la pantalla de verificación.',
            'Login con Google disponible (OAuth). El cliente puede usar Google en vez de crear contraseña.',
            'Si sospechan acceso no autorizado: cambiar contraseña de inmediato y contactar soporte urgente.',
            'Eliminar cuenta: requiere confirmación vía soporte (acción irreversible, se pierden pedidos e historial de Lirios).',
            '',
            '— PROGRAMA LIRIOS (monedero interno de puntos) —',
            'Los Lirios son la moneda interna de fidelización de Lyrium.',
            'Se ganan al realizar compras en tiendas participantes; el monto acreditado depende de la tienda.',
            'Para ver el saldo: panel → sección "Lirios" (balance actual + historial de transacciones).',
            'Para canjear: en el checkout aparece la opción "Aplicar Lirios" si hay saldo y la tienda lo permite.',
            'Cada tienda define el porcentaje máximo que puede pagarse con Lirios (campo lirios_percent).',
            'Los Lirios no tienen fecha de vencimiento (la política puede cambiar con previo aviso).',
            'Los Lirios no son transferibles entre cuentas.',
            '',
            '— PROGRAMA DE FIDELIDAD / LOYALTY —',
            'Lyrium tiene un sistema de tiers de lealtad (LoyaltyProgram). Los clientes acumulan puntos por compras y pueden subir de nivel para obtener beneficios adicionales.',
            'Para ver el estado de fidelidad: panel de usuario → sección de lealtad/puntos.',
            '',
            '— DISPUTAS —',
            'Si el pedido no llegó, llegó dañado o incorrecto: panel → "Mis pedidos" → seleccionar pedido → "Abrir disputa".',
            'El equipo de Lyrium media entre cliente y vendedor. Plazo de resolución: hasta 7 días hábiles.',
            '',
            '— RESEÑAS —',
            'Solo compradores que recibieron el producto pueden dejar reseña.',
            'Cómo dejarla: panel → "Mis pedidos" → seleccionar pedido entregado → "Dejar reseña".',
            '',
            '— SERVICIOS (citas y reservas) —',
            'Lyrium también ofrece servicios de salud y bienestar (terapias, consultas, etc.).',
            'Para adquirir: buscar el servicio → "Reservar" → completar pago. La tienda contacta al cliente para coordinar horario y especialista.',
            'Para cancelar una reserva: contactar directamente a la tienda desde el detalle del pedido. Cada tienda tiene su política de cancelación.',
            '',
            '— SOPORTE —',
            'Canales disponibles para el cliente:',
            '  · WhatsApp: https://wa.me/51937093420 (lun–vie 9:00–18:00, sáb 9:00–13:00)',
            '  · Ticket de soporte: panel → "Soporte" → "Nuevo ticket"',
            '  · Redes sociales: @lyrium_biomarketplace en Instagram, Facebook y TikTok',
            'Tiempo de respuesta: máximo 1 día hábil.',
            '',
            '— NOTIFICACIONES —',
            'El cliente puede gestionar notificaciones push y por email desde: perfil → "Configuración" → "Notificaciones".',
            'Las notificaciones push requieren permiso del navegador la primera vez.',
            '',
            '— PROMOCIONES Y CUPONES —',
            'Las promociones las gestiona cada tienda por separado. Se ven en la página principal y en el perfil de cada tienda.',
            'Para usar un cupón: en el checkout, campo "Código de descuento" → ingresar código → "Aplicar".',
            '',
            '— SEGURIDAD Y PRIVACIDAD —',
            'Lyrium cumple la Ley de Protección de Datos Personales del Perú (Ley N° 29733).',
            'Los pagos se procesan en pasarelas certificadas; Lyrium no almacena datos de tarjeta.',
            'No se comparte información personal del cliente con terceros sin consentimiento.',
            '',
            '— APP MÓVIL —',
            'Actualmente Lyrium es una plataforma web optimizada para móviles. No hay app nativa todavía (en desarrollo).',
        ];
    }

    private function sellerKnowledge(): array
    {
        return [
            '— PLANES Y COMISIONES —',
            'Lyrium ofrece planes de suscripción con diferentes porcentajes de comisión:',
            '  1. Plan Emprende: 5% de comisión por venta.',
            '  2. Plan Crece: 10% de comisión por venta.',
            '  3. Plan Especial: 15% de comisión por venta.',
            'Cada plan incluye beneficios adicionales como mayor visibilidad, herramientas de gestión y soporte prioritario.',
            'Puedes consultar los detalles de cada plan en la sección de Planes de tu panel de vendedor.',
            '',
            '— GESTIÓN DE CATÁLOGO —',
            'Para publicar productos o servicios: ve a tu panel de vendedor → Catálogo → Nuevo producto/servicio.',
            'Estados de aprobación: el producto se crea en estado "draft", luego lo envías a revisión → "pending_review" → el administrador lo aprueba o rechaza.',
            'Productos aprobados: estado "approved". Productos rechazados: estado "rejected" (el admin deja un motivo).',
            'Puedes editar productos aprobados, pero volverán a "pending_review" si modificas campos sensibles.',
            '',
            '— PEDIDOS Y VENTAS —',
            'Para ver tus ventas: panel de vendedor → Ventas. Allí ves el historial completo con montos, estados y datos del cliente.',
            'Estados del pedido desde la tienda: Pendiente → Confirmar → En preparación → Enviar → Entregado.',
            'Cuando confirmas un pedido, el pago se procesa y el stock se descuenta.',
            'Para despachar: actualiza el estado a "Enviado" e ingresa el número de tracking si aplica.',
            '',
            '— PAGOS AL VENDEDOR —',
            'Los pagos al vendedor se procesan periódicamente según la comisión del plan contratado.',
            'Puedes ver tus pagos y comisiones en el panel de vendedor → Finanzas.',
            'El detalle de cada venta muestra el monto bruto, la comisión de Lyrium y el neto a recibir.',
            '',
            '— LIRIOS DESDE LA TIENDA —',
            'Cada tienda controla el porcentaje máximo de descuento que puede aplicarse con Lirios.',
            'Campo disponible en la configuración de la tienda: lirios_percent.',
            'El tope global del sistema es del 3% del valor del pedido.',
            '',
            '— SOPORTE PARA VENDEDORES —',
            'Canales de soporte para vendedores:',
            '  · WhatsApp: https://wa.me/51937093420 (lun–vie 9:00–18:00, sáb 9:00–13:00)',
            '  · Ticket de soporte desde tu panel de vendedor → Ayuda → Nuevo ticket',
            '  · También puedes usar el chat de la plataforma para consultas rápidas.',
            '',
            '— IMPORTANTE —',
            'No inventes cifras exactas de comisiones, costos o fechas que no estén en esta base de conocimiento.',
            'No reveles datos de otros vendedores, sus ventas, tiendas o información financiera.',
            'Si no tienes la información exacta, dirige al vendedor a su panel o a soporte.',
        ];
    }

    private function adminKnowledge(): array
    {
        return [
            'Este usuario es staff interno de Lyrium (administrador o seguridad), no es cliente ni vendedor.',
            '',
            'Puede preguntar sobre:',
            '  · Gestión de tickets de soporte y atención al cliente.',
            '  · Moderación de tiendas: aprobar/rechazar tiendas, gestionar estados.',
            '  · Moderación de productos: aprobar/rechazar productos y servicios.',
            '  · Configuración del sistema, planes, comisiones y usuarios.',
            '  · Auditoría (security_admin específicamente): registros de auditoría, alertas de seguridad, IPs bloqueadas.',
            '',
            'SI el administrador pregunta algo sobre el flujo de cliente o vendedor (cómo comprar, cómo vender, etc.),',
            'puedes responder igual porque es información pública de la plataforma.',
            'PERO si la pregunta es ambigua, prioriza dar una respuesta de nivel operativo interno.',
            '',
            'No hay secciones específicas de cliente o vendedor para este rol — el admin conoce la plataforma',
            'a nivel de gestión y configuración.',
        ];
    }

    private function logisticsKnowledge(): array
    {
        return [
            '— GESTIÓN DE ENVÍOS —',
            'El operador logístico gestiona el despacho de pedidos y la actualización de estados de shipment.',
            'Puede consultar y actualizar: zonas de envío, tarifas de transporte, métodos de envío disponibles.',
            'Los shipments se gestionan desde el panel logístico.',
            '',
            'Los estados de envío incluyen: pendiente, en tránsito, entregado, devuelto.',
            'Cada vez que se actualiza un estado, el cliente recibe una notificación.',
            '',
            '— ZONAS Y TARIFAS —',
            'Las zonas de envío y tarifas las configura el administrador.',
            'El operador logístico puede consultar las tarifas vigentes y los métodos disponibles por zona.',
        ];
    }

    private function roleLabel(?string $role): string
    {
        return match ($role) {
            'seller' => 'vendedor',
            'administrator' => 'administrador',
            'security_admin' => 'administrador de seguridad',
            'logistics_operator' => 'operador logístico',
            'customer' => 'cliente',
            default => 'visitante (sin cuenta / no identificado)',
        };
    }
}
