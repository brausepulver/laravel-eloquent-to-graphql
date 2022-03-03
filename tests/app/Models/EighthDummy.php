<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EighthDummy extends Model
{
    public function dummyable(): MorphTo
    {
        return $this->morphTo('dummyable');
    }
}
