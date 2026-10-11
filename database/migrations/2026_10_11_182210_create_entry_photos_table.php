<?php

declare(strict_types=1);

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
        Schema::create('entry_photos', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('entry_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('thumb_path')->nullable();
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('bytes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_photos');
    }
};
