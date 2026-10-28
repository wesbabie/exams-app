<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttemptEvent extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }
}
