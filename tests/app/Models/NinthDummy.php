<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NinthDummy extends Model
{
    public function alsoDummyable(): MorphTo
    {
        return $this->morphTo('alsoDummyable');
    }
}
