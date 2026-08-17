<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case Draft = 'Draft';
    case Published = 'Published';
}
