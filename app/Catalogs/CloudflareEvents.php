<?php

declare(strict_types=1);

namespace App\Catalogs;

final class CloudflareEvents
{
    const CONFIG_UPDATED = 'cloudflare.config.updated';
    const WAF_RULE_CREATED = 'cloudflare.waf.rule.created';
    const WAF_RULE_UPDATED = 'cloudflare.waf.rule.updated';
    const WAF_RULE_DELETED = 'cloudflare.waf.rule.deleted';
    const FIREWALL_IP_ADDED = 'cloudflare.firewall.ip.added';
    const FIREWALL_IP_REMOVED = 'cloudflare.firewall.ip.removed';
}
