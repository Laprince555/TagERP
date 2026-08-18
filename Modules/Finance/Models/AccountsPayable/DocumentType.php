<?php

namespace Modules\Finance\Models\AccountsPayable;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\DocumentTypeFactory;

/**
 * A kind of AP document (invoice, credit note, debit note, ...). The
 * required key on an AP GL Setup — every routing configuration exists for
 * exactly one document type.
 *
 * @property string $code
 * @property string $name
 */
class DocumentType extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-ap-doc';

    protected $table = 'finance_ap_document_types';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DocumentType $type): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($type->slug)) {
                $type->slug = $builder->uniqueSlug($type->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($type->code)) {
                $type->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $type->slug);
            }
        });
    }

    protected static function newFactory(): DocumentTypeFactory
    {
        return DocumentTypeFactory::new();
    }
}
