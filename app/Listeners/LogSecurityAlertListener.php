<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CriticalSecurityEvent;
use App\Models\AuditLog;
use App\Models\SecurityAlert;
use Illuminate\Support\Facades\Log;

final class LogSecurityAlertListener
{
    public function handle(CriticalSecurityEvent $event): void
    {
        $log = $event->auditLog;

        try {
            SecurityAlert::create([
                'audit_log_id' => $log->id,
                'type' => $this->resolveAlertType($log->event),
                'title' => $this->resolveTitle($log),
                'message' => $log->description,
                'severity' => $log->severity ?? 'critical',
                'status' => SecurityAlert::STATUS_OPEN,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at ?? now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('LogSecurityAlertListener: fallo al crear alerta de seguridad', [
                'event' => $log->event,
                'audit_log_id' => $log->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveAlertType(string $event): string
    {
        if (str_starts_with($event, 'security.')) {
            return 'security';
        }

        if (str_starts_with($event, 'auth.')) {
            return 'auth';
        }

        if (str_starts_with($event, 'system.')) {
            return 'system';
        }

        return 'critical';
    }

    private function resolveTitle(AuditLog $log): string
    {
        $titles = [
            'users.deleted' => 'Eliminación de usuario',
            'users.banned' => 'Usuario suspendido',
            'stores.deleted' => 'Tienda eliminada',
            'stores.approved' => 'Tienda aprobada',
            'stores.suspended' => 'Tienda suspendida',
            'stores.banned' => 'Tienda bloqueada',
            'products.deleted' => 'Producto eliminado',
            'services.deleted' => 'Servicio eliminado',
            'orders.cancelled' => 'Pedido cancelado',
            'orders.refunded' => 'Reembolso realizado',
            'orders.refund.partial' => 'Reembolso parcial',
            'payments.payout.failed' => 'Pago fallido',
            'invoices.rejected' => 'Factura rechazada',
            'subscriptions.payment.failed' => 'Suscripción: pago fallido',
            'plans.deleted' => 'Plan eliminado',
            'contracts.deleted' => 'Contrato eliminado',
            'contracts.terminated' => 'Contrato terminado',
            'roles.deleted' => 'Rol eliminado',
            'roles.permissions.assigned' => 'Permisos asignados',
            'roles.permissions.revoked' => 'Permisos revocados',
            'config.commissions.updated' => 'Comisiones actualizadas',
            'config.security.updated' => 'Configuración de seguridad actualizada',
            'system.exception' => 'Excepción del sistema',
            'system.health.check.failed' => 'Health check fallido',
            'security.ip.blocked' => 'IP bloqueada',
            'security.alert.triggered' => 'Alerta de seguridad',
            'security.protection.rule.triggered' => 'Regla de protección activada',
        ];

        return $titles[$log->event] ?? "Evento crítico: {$log->event}";
    }
}
