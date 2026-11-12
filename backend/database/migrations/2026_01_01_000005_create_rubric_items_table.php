<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubric_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('page_number');
            $table->unsignedTinyInteger('item_order');
            $table->string('title');
            $table->json('indicators');
            $table->decimal('weight', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_items');
    }
};
