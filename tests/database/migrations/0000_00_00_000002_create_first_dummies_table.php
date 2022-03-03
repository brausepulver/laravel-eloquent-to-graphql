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
        Schema::create('first_dummies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('dummy_column_1');
            $table->string('dummy_column_2')->nullable();
            $table->integer('dummy_column_3');

            $table->foreignId('sixth_dummy_id')->constrained();
            $table->foreignId('sixth_dummy_nullable_id')->nullable()->references('id')->on('sixth_dummies');
            $table->foreignId('external_dummy_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('first_dummies');
    }
};
