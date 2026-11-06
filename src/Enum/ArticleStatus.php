<?php

namespace App\Enum;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
