<?php

namespace Tests\Feature\DynamicTable\Support;

use Illuminate\Database\Eloquent\Model;

class DtAuthor extends Model
{
    protected $table = 'dt_test_authors';

    public $timestamps = false;

    protected $fillable = ['name', 'country'];

    public function posts()
    {
        return $this->hasMany(DtPost::class, 'author_id');
    }
}
