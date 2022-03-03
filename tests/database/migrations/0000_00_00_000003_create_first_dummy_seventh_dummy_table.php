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
        Schema::create('first_dummy_seventh_dummy', function (Blueprint $table) {
            $table->foreignId('first_dummy_id')->constrained();
            $table->foreignId('first_dummy_nullable_id')->nullable()->references('id')->on('first_dummies');
            $table->foreignId('seventh_dummy_id')->constrained();
            $table->foreignId('seventh_dummy_nullable_id')->nullable()->references('id')->on('seventh_dummies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('first_dummy_seventh_dummy');
    }
};
