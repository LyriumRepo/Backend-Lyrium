<?php

declare(strict_types=1);

namespace App\Catalogs;

final class ContentEvents
{
    const REPORTED = 'content.reported';
    const RESOLVED = 'content.report.resolved';
    const DISMISSED = 'content.report.dismissed';
}
