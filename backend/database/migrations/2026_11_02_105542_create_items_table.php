<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 120);
            $table->text('body');
            $table->string('submitter_name', 80);
            // A plain string rather than a DB enum: SQLite has no enum type, and the
            // ItemStatus cast already restricts values, so new statuses need no migration.
            $table->string('status', 16)->default('pending');
            $table->unsignedTinyInteger('risk_score');
            $table->json('flags');
            $table->string('suggested_action', 16);
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('risk_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
