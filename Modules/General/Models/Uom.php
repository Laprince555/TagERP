<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit of measure (each, kg, litre, ...) — a flat reference lookup, not a
 * navigable Application, so it carries no hierarchical code.
 *
 * @property string $code
 * @property string $name
 */
class Uom extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'uom';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'description',
    ];
}
