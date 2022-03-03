<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use MyPackage\Models\ExternalDummy;

class FirstDummy extends Model
{
    public function secondDummy(): HasOne
    {
        return $this->hasOne(SecondDummy::class);
    }

    public function secondDummyNullable(): HasOne
    {
        return $this->hasOne(SecondDummy::class, 'first_dummy_nullable_id');
    }

    public function thirdDummies(): HasMany
    {
        return $this->hasMany(ThirdDummy::class);
    }

    public function thirdDummiesNullable(): HasMany
    {
        return $this->hasMany(ThirdDummy::class, 'first_dummy_nullable_id');
    }

    public function fourthDummy(): HasOneThrough
    {
        return $this->hasOneThrough(FourthDummy::class, SecondDummy::class);
    }

    public function fourthDummyNullableFirst(): HasOneThrough
    {
        return $this->hasOneThrough(FourthDummy::class, SecondDummy::class, 'first_dummy_nullable_id');
    }

    public function fourthDummyNullableSecond(): HasOneThrough
    {
        return $this->hasOneThrough(FourthDummy::class, SecondDummy::class, 'first_dummy_id', 'second_dummy_nullable_id');
    }

    public function fourthDummyNullableThird(): HasOneThrough
    {
        return $this->hasOneThrough(FourthDummy::class, SecondDummy::class, 'first_dummy_nullable_id', 'second_dummy_nullable_id');
    }

    public function fifthDummies(): HasManyThrough
    {
        return $this->hasManyThrough(FifthDummy::class, SecondDummy::class);
    }

    public function fifthDummiesNullableFirst(): HasManyThrough
    {
        return $this->hasManyThrough(FifthDummy::class, SecondDummy::class, 'first_dummy_nullable_id');
    }

    public function fifthDummiesNullableSecond(): HasManyThrough
    {
        return $this->hasManyThrough(FifthDummy::class, SecondDummy::class, 'first_dummy_id', 'second_dummy_nullable_id');
    }

    public function fifthDummiesNullableThird(): HasManyThrough
    {
        return $this->hasManyThrough(FifthDummy::class, SecondDummy::class, 'first_dummy_nullable_id', 'second_dummy_nullable_id');
    }

    public function sixthDummy(): BelongsTo
    {
        return $this->belongsTo(SixthDummy::class);
    }

    public function sixthDummyNullable(): BelongsTo
    {
        return $this->belongsTo(SixthDummy::class, 'sixth_dummy_nullable_id');
    }

    public function seventhDummies(): BelongsToMany
    {
        return $this->belongsToMany(SeventhDummy::class);
    }

    public function seventhDummiesNullableFirst(): BelongsToMany
    {
        return $this->belongsToMany(SeventhDummy::class, foreignPivotKey: 'first_dummy_nullable_id');
    }

    public function seventhDummiesNullableSecond(): BelongsToMany
    {
        return $this->belongsToMany(SeventhDummy::class, relatedPivotKey: 'seventh_dummy_nullable_id');
    }

    public function seventhDummiesNullableThird(): BelongsToMany
    {
        return $this->belongsToMany(SeventhDummy::class, foreignPivotKey: 'first_dummy_nullable_id', relatedPivotKey: 'seventh_dummy_nullable_id');
    }

    public function eighthDummy(): MorphOne
    {
        return $this->morphOne(EighthDummy::class, 'dummyable');
    }

    public function ninthDummies(): MorphMany
    {
        return $this->morphMany(NinthDummy::class, 'also_dummyable');
    }
    
    public function tenthDummies(): MorphToMany
    {
        return $this->morphToMany(TenthDummy::class, 'also_also_dummyable');
    }

    public function tenthDummiesNullable(): MorphToMany
    {
        return $this->morphToMany(TenthDummy::class, 'also_also_dummyable', relatedPivotKey: 'tenth_dummy_nullable_id');
    }

    public function externalDummy(): BelongsTo
    {
        return $this->belongsTo(ExternalDummy::class);
    }
}
