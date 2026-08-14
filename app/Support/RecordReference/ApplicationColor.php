<?php

namespace App\Support\RecordReference;

/**
 * Fixed, trusted palette of Application accent colors. This is the single
 * allowlist for the `applications.color` column — never store or render an
 * arbitrary Tailwind class, CSS string, or hex value.
 */
enum ApplicationColor: string
{
    case Sky = 'sky';
    case Indigo = 'indigo';
    case Violet = 'violet';
    case Amber = 'amber';
    case Emerald = 'emerald';
    case Rose = 'rose';
    case Cyan = 'cyan';
    case Orange = 'orange';
    case Slate = 'slate';
}
