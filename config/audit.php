<?php

declare(strict_types=1);

return [
    /*
    | Severidad por defecto para cada evento.
    | Única fuente de verdad. NO duplicar esta información en los catálogos.
    | Si un evento no está listado, se usa 'info'.
    */
    'severity' => [
        // ─── Auth ─────────────────────────────────────
        'auth.login.success' => 'info',
        'auth.login.failed' => 'warning',
        'auth.logout' => 'info',
        'auth.register' => 'info',
        'auth.register.customer' => 'info',
        'auth.email.verified' => 'info',
        'auth.email.changed' => 'warning',
        'auth.password.changed' => 'warning',
        'auth.password.reset.requested' => 'info',
        'auth.password.reset.completed' => 'info',
        'auth.oauth.login' => 'info',
        'auth.token.refreshed' => 'info',
        'auth.token.revoked' => 'warning',

        // ─── Users ────────────────────────────────────
        'users.created' => 'info',
        'users.updated' => 'info',
        'users.deleted' => 'critical',
        'users.restored' => 'info',
        'users.role.changed' => 'warning',
        'users.banned' => 'critical',
        'users.unbanned' => 'warning',
        'users.avatar.changed' => 'info',
        'users.settings.changed' => 'info',

        // ─── Roles ────────────────────────────────────
        'roles.created' => 'info',
        'roles.updated' => 'warning',
        'roles.deleted' => 'critical',
        'roles.permissions.assigned' => 'warning',
        'roles.permissions.revoked' => 'warning',
        'roles.users.assigned' => 'warning',
        'roles.users.revoked' => 'warning',

        // ─── Stores ───────────────────────────────────
        'stores.created' => 'info',
        'stores.updated' => 'info',
        'stores.deleted' => 'critical',
        'stores.restored' => 'info',
        'stores.approved' => 'critical',
        'stores.rejected' => 'warning',
        'stores.suspended' => 'critical',
        'stores.banned' => 'critical',
        'stores.profile.requested' => 'info',
        'stores.profile.approved' => 'info',
        'stores.profile.rejected' => 'info',
        'stores.member.added' => 'info',
        'stores.member.removed' => 'warning',
        'stores.member.role.changed' => 'warning',
        'stores.branch.created' => 'info',
        'stores.branch.updated' => 'info',
        'stores.branch.deleted' => 'warning',
        'stores.media.uploaded' => 'info',
        'stores.media.deleted' => 'warning',

        // ─── Products ─────────────────────────────────
        'products.created' => 'info',
        'products.updated' => 'info',
        'products.deleted' => 'critical',
        'products.restored' => 'info',
        'products.status.changed' => 'warning',
        'products.price.changed' => 'warning',
        'products.stock.changed' => 'warning',
        'products.media.uploaded' => 'info',
        'products.media.deleted' => 'info',
        'products.attributes.updated' => 'info',

        // ─── Services ─────────────────────────────────
        'services.created' => 'info',
        'services.updated' => 'info',
        'services.deleted' => 'critical',
        'services.status.changed' => 'warning',
        'services.price.changed' => 'warning',
        'services.schedule.updated' => 'info',
        'services.media.uploaded' => 'info',
        'services.media.deleted' => 'info',

        // ─── Specialists ──────────────────────────────
        'specialists.created' => 'info',
        'specialists.updated' => 'info',
        'specialists.deleted' => 'warning',
        'specialists.assigned' => 'info',
        'specialists.unassigned' => 'info',
        'specialists.schedule.updated' => 'info',

        // ─── Bookings ─────────────────────────────────
        'bookings.created' => 'info',
        'bookings.confirmed' => 'info',
        'bookings.cancelled' => 'warning',
        'bookings.rescheduled' => 'info',
        'bookings.completed' => 'info',
        'bookings.no.show' => 'warning',

        // ─── Orders ───────────────────────────────────
        'orders.created' => 'info',
        'orders.status.changed' => 'warning',
        'orders.cancelled' => 'critical',
        'orders.refunded' => 'critical',
        'orders.refund.partial' => 'critical',
        'orders.shipped' => 'info',
        'orders.delivered' => 'info',
        'orders.item.status.changed' => 'info',
        'orders.payment.confirmed' => 'info',
        'orders.payment.failed' => 'warning',
        'orders.receipt.requested' => 'info',

        // ─── Payments ─────────────────────────────────
        'payments.method.created' => 'info',
        'payments.method.deleted' => 'warning',
        'payments.transaction.completed' => 'info',
        'payments.transaction.failed' => 'warning',
        'payments.webhook.received' => 'warning',
        'payments.payout.processed' => 'info',
        'payments.payout.failed' => 'critical',

        // ─── Invoices ─────────────────────────────────
        'invoices.generated' => 'info',
        'invoices.sent.to.sunat' => 'info',
        'invoices.accepted' => 'info',
        'invoices.observed' => 'warning',
        'invoices.rejected' => 'critical',
        'invoices.retried' => 'warning',

        // ─── Subscriptions ────────────────────────────
        'subscriptions.created' => 'info',
        'subscriptions.changed' => 'warning',
        'subscriptions.cancelled' => 'warning',
        'subscriptions.expired' => 'info',
        'subscriptions.payment.scheduled' => 'info',
        'subscriptions.payment.completed' => 'info',
        'subscriptions.payment.failed' => 'critical',

        // ─── Plans ────────────────────────────────────
        'plans.created' => 'info',
        'plans.updated' => 'warning',
        'plans.deleted' => 'critical',
        'plans.status.changed' => 'warning',
        'plans.request.created' => 'info',
        'plans.request.approved' => 'info',
        'plans.request.rejected' => 'info',

        // ─── Shipping ─────────────────────────────────
        'shipments.created' => 'info',
        'shipments.status.changed' => 'info',
        'shipments.tracking.updated' => 'info',
        'shipping.zones.updated' => 'warning',
        'shipping.rates.updated' => 'warning',
        'shipping.methods.updated' => 'warning',

        // ─── Returns ──────────────────────────────────
        'returns.created' => 'info',
        'returns.approved' => 'info',
        'returns.rejected' => 'info',
        'returns.received' => 'info',
        'returns.refunded' => 'info',

        // ─── Disputes ─────────────────────────────────
        'disputes.created' => 'warning',
        'disputes.message.added' => 'info',
        'disputes.resolved' => 'info',
        'disputes.closed' => 'info',

        // ─── Categories ───────────────────────────────
        'categories.created' => 'info',
        'categories.updated' => 'info',
        'categories.deleted' => 'warning',

        // ─── Reviews ──────────────────────────────────
        'reviews.created' => 'info',
        'reviews.updated' => 'info',
        'reviews.deleted' => 'warning',
        'reviews.moderated' => 'warning',
        'reviews.reported' => 'info',

        // ─── Tickets ──────────────────────────────────
        'tickets.created' => 'info',
        'tickets.status.changed' => 'info',
        'tickets.assigned' => 'info',
        'tickets.closed' => 'info',
        'tickets.message.added' => 'info',
        'tickets.priority.changed' => 'warning',

        // ─── Conversations ────────────────────────────
        'conversations.created' => 'info',

        // ─── Loyalty ──────────────────────────────────
        'loyalty.points.earned' => 'info',
        'loyalty.points.redeemed' => 'info',
        'loyalty.points.expired' => 'info',
        'loyalty.tier.changed' => 'info',
        'loyalty.reward.created' => 'info',
        'loyalty.reward.updated' => 'info',
        'loyalty.reward.deleted' => 'info',
        'loyalty.reward.redeemed' => 'info',

        // ─── Coupons ──────────────────────────────────
        'coupons.created' => 'info',
        'coupons.updated' => 'info',
        'coupons.deleted' => 'warning',
        'coupons.redeemed' => 'info',
        'coupons.validated' => 'info',

        // ─── Blog ─────────────────────────────────────
        'blog.post.created' => 'info',
        'blog.post.updated' => 'info',
        'blog.post.deleted' => 'warning',
        'blog.post.status.changed' => 'info',
        'blog.comment.created' => 'info',
        'blog.comment.deleted' => 'info',

        // ─── Forum ────────────────────────────────────
        'forum.topic.created' => 'info',
        'forum.topic.deleted' => 'warning',
        'forum.post.created' => 'info',
        'forum.post.deleted' => 'info',
        'forum.post.reported' => 'warning',

        // ─── Content ──────────────────────────────────
        'content.reported' => 'warning',
        'content.report.resolved' => 'info',
        'content.report.dismissed' => 'info',

        // ─── Glossary ─────────────────────────────────
        'glossary.entry.created' => 'info',
        'glossary.entry.updated' => 'info',
        'glossary.entry.deleted' => 'warning',
        'glossary.term.suggested' => 'info',
        'glossary.term.approved' => 'info',
        'glossary.term.rejected' => 'info',

        // ─── Contracts ────────────────────────────────
        'contracts.created' => 'info',
        'contracts.updated' => 'warning',
        'contracts.deleted' => 'critical',
        'contracts.signed' => 'warning',
        'contracts.activated' => 'warning',
        'contracts.expired' => 'info',
        'contracts.terminated' => 'critical',
        'contracts.document.uploaded' => 'info',

        // ─── Suppliers ────────────────────────────────
        'suppliers.created' => 'info',
        'suppliers.updated' => 'info',
        'suppliers.deleted' => 'warning',

        // ─── Expenses ─────────────────────────────────
        'expenses.created' => 'info',
        'expenses.updated' => 'info',
        'expenses.deleted' => 'warning',

        // ─── Operational Roles ─────────────────────────
        'operational_roles.created' => 'info',
        'operational_roles.updated' => 'info',
        'operational_roles.deleted' => 'warning',
        'operational_roles.toggled' => 'warning',
        'operational_roles.user.assigned' => 'warning',
        'operational_roles.user.removed' => 'warning',
        'operational_roles.2fa.required' => 'warning',

        // ─── Config ───────────────────────────────────
        'config.system.updated' => 'warning',
        'config.colors.updated' => 'info',
        'config.commissions.updated' => 'critical',
        'config.security.updated' => 'critical',
        'config.shipping.updated' => 'warning',
        'config.loyalty.updated' => 'info',

        // ─── Media ────────────────────────────────────
        'media.uploaded' => 'info',
        'media.deleted' => 'info',

        // ─── Security ─────────────────────────────────
        'security.session.revoked' => 'warning',
        'security.session.terminated' => 'warning',
        'security.ip.blocked' => 'critical',
        'security.ip.unblocked' => 'warning',
        'security.ip.flagged' => 'warning',
        'security.ip.whitelisted' => 'info',
        'security.alert.triggered' => 'critical',
        'security.alert.dismissed' => 'info',
        'security.alert.resolved' => 'info',
        'security.protection.rule.created' => 'info',
        'security.protection.rule.updated' => 'warning',
        'security.protection.rule.deleted' => 'warning',
        'security.protection.rule.triggered' => 'critical',
        'security.rate.limit.exceeded' => 'warning',
        'security.2fa.enabled' => 'warning',
        'security.2fa.disabled' => 'warning',
        'security.2fa.failed' => 'warning',
        'security.audit.settings.changed' => 'warning',

        // ─── Cloudflare ───────────────────────────────
        'cloudflare.config.updated' => 'warning',
        'cloudflare.waf.rule.created' => 'info',
        'cloudflare.waf.rule.updated' => 'info',
        'cloudflare.waf.rule.deleted' => 'warning',
        'cloudflare.firewall.ip.added' => 'warning',
        'cloudflare.firewall.ip.removed' => 'warning',

        // ─── System ───────────────────────────────────
        'system.exception' => 'critical',
        'system.error' => 'warning',
        'system.cache.cleared' => 'info',
        'system.maintenance.enabled' => 'warning',
        'system.maintenance.disabled' => 'warning',
        'system.queue.failed' => 'warning',
        'system.scheduler.executed' => 'info',
        'system.database.backup' => 'info',
        'system.health.check.failed' => 'critical',
    ],

    /*
    | Eventos que deben procesarse de forma asíncrona (cola).
    | Por defecto, solo los INFO y WARNING se encolan.
    */
    'async_events' => true,

    /*
    | Umbrales para detección de patrones
    */
    'patterns' => [
        'failed_login' => [
            'threshold' => 5,
            'window_minutes' => 5,
        ],
    ],

    /*
    | Retención en meses
    */
    'retention' => [
        'live' => 12,
        'archive' => 36,
    ],
];
