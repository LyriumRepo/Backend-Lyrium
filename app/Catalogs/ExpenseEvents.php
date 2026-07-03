<?php

declare(strict_types=1);

namespace App\Catalogs;

final class ExpenseEvents
{
    const CREATED = 'expenses.created';
    const UPDATED = 'expenses.updated';
    const DELETED = 'expenses.deleted';
}
