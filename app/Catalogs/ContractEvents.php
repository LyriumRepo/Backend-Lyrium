<?php

declare(strict_types=1);

namespace App\Catalogs;

final class ContractEvents
{
    const CREATED = 'contracts.created';
    const UPDATED = 'contracts.updated';
    const DELETED = 'contracts.deleted';
    const SIGNED = 'contracts.signed';
    const ACTIVATED = 'contracts.activated';
    const EXPIRED = 'contracts.expired';
    const TERMINATED = 'contracts.terminated';
    const DOCUMENT_UPLOADED = 'contracts.document.uploaded';
}
