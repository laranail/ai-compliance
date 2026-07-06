<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// the provider_id column shipped unconstrained before the providers table
// existed; an index covers the dashboard and export lookups. a real foreign
// key is deliberately omitted: sqlite cannot add one to an existing table,
// and soft-deleted providers must stay referenceable anyway.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->table(), function (Blueprint $table): void {
            $table->index('provider_id');
        });
    }

    public function down(): void
    {
        Schema::table($this->table(), function (Blueprint $table): void {
            $table->dropIndex(['provider_id']);
        });
    }

    private function table(): string
    {
        return (string) config('laranail.ai-compliance.tables.activity_events', 'ai_activity_events');
    }
};
