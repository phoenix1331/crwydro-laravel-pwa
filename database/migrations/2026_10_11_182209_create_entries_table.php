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
        Schema::create('entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->string('tag', 16);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->decimal('accuracy_m', 8, 2)->nullable();
            $table->decimal('altitude_m', 8, 2)->nullable();
            $table->timestamp('recorded_at', 3);
            $table->timestamp('client_updated_at', 3);
            $table->softDeletes(precision: 3);
            $table->timestamps(3);

            $table->index(['user_id', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
