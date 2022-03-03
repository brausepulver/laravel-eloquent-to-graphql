<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class TenthDummy extends Model
{
    public function firstDummies(): MorphToMany
    {
        return $this->morphedByMany(FirstDummy::class, 'alsoAlsoDummyable');
    }
}
