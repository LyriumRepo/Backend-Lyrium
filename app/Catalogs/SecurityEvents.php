<?php

declare(strict_types=1);

namespace App\Catalogs;

final class SecurityEvents
{
    const SESSION_REVOKED = 'security.session.revoked';
    const SESSION_TERMINATED = 'security.session.terminated';
    const IP_BLOCKED = 'security.ip.blocked';
    const IP_UNBLOCKED = 'security.ip.unblocked';
    const IP_FLAGGED = 'security.ip.flagged';
    const IP_WHITELISTED = 'security.ip.whitelisted';
    const ALERT_TRIGGERED = 'security.alert.triggered';
    const ALERT_DISMISSED = 'security.alert.dismissed';
    const ALERT_RESOLVED = 'security.alert.resolved';
    const PROTECTION_RULE_CREATED = 'security.protection.rule.created';
    const PROTECTION_RULE_UPDATED = 'security.protection.rule.updated';
    const PROTECTION_RULE_DELETED = 'security.protection.rule.deleted';
    const PROTECTION_RULE_TRIGGERED = 'security.protection.rule.triggered';
    const RATE_LIMIT_EXCEEDED = 'security.rate.limit.exceeded';
    const TWOFA_ENABLED = 'security.2fa.enabled';
    const TWOFA_DISABLED = 'security.2fa.disabled';
    const TWOFA_FAILED = 'security.2fa.failed';
    const AUDIT_SETTINGS_CHANGED = 'security.audit.settings.changed';
}
