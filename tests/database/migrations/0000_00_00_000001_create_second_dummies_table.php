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
        Schema::create('second_dummies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('first_dummy_id')->constrained();
            $table->foreignId('first_dummy_nullable_id')->nullable()->references('id')->on('first_dummies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('second_dummies');
    }
};
