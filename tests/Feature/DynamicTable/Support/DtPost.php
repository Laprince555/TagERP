<?php

namespace Tests\Feature\DynamicTable\Support;

use Illuminate\Database\Eloquent\Model;

class DtPost extends Model
{
    protected $table = 'dt_test_posts';

    public $timestamps = false;

    protected $fillable = ['author_id', 'title', 'views', 'published_at', 'active'];

    protected function casts(): array
    {
        return [
            'published_at' => 'date',
            'active' => 'boolean',
        ];
    }

    public function author()
    {
        return $this->belongsTo(DtAuthor::class, 'author_id');
    }
}
