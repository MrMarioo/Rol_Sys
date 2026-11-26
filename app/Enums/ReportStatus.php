<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Archived = 'archived';
}
