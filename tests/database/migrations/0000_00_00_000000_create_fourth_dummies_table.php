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
        Schema::create('fourth_dummies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('second_dummy_id')->constrained();
            $table->foreignId('second_dummy_nullable_id')->nullable()->references('id')->on('second_dummies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fourth_dummies');
    }
};
