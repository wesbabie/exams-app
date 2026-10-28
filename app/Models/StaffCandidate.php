<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffCandidate extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->surname));
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ExamRegistration::class, 'candidate_id')->where('candidate_type', 'staff');
    }
}
