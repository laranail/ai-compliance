<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();       // same export rule as consent records
            $table->string('tenant_id')->nullable()->index();
            $table->string('event_type')->index();     // ActivityType enum
            $table->configuredNullableMorphs('actorable');   // who acted; null for system/scheduler events
            $table->configuredNullableMorphs('subjectable'); // who it was about
            $table->unsignedBigInteger('provider_id')->nullable(); // fk constraint arrives with the providers table
            $table->json('context')->nullable();       // no raw prompts or sensitive content
            $table->string('hash_prev', 64)->nullable(); // tamper-evidence chain, wired in the activity milestone
            $table->timestamp('recorded_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return (string) config('laranail.ai-compliance.tables.activity_events', 'ai_activity_events');
    }
};
