<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();
            $table->string('first_name', 255);
            $table->string('last_name', 255);
            // gender: 1=男性, 2=女性, 3=その他
            $table->tinyInteger('gender');
            $table->string('email', 255);
            // tel: ハイフンなし10〜11桁
            $table->string('tel', 11);
            $table->string('address', 255);
            $table->string('building', 255)->nullable();
            $table->string('detail', 120);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
