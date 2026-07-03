<?php

declare(strict_types=1);

namespace App\Catalogs;

final class GlossaryEvents
{
    const ENTRY_CREATED = 'glossary.entry.created';
    const ENTRY_UPDATED = 'glossary.entry.updated';
    const ENTRY_DELETED = 'glossary.entry.deleted';
    const TERM_SUGGESTED = 'glossary.term.suggested';
    const TERM_APPROVED = 'glossary.term.approved';
    const TERM_REJECTED = 'glossary.term.rejected';
}
