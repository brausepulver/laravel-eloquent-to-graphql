<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('also_also_dummyables', function (Blueprint $table) {
            $table->foreignId('tenth_dummy_id')->constrained();
            $table->foreignId('tenth_dummy_nullable_id')->nullable()->references('id')->on('tenth_dummies');
            $table->morphs('also_also_dummyable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('also_also_dummyables');
    }
};
