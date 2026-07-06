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
            $table->ulid('public_id')->unique();       // the only id exports and webhooks ever emit
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('consent_type_id')->constrained($this->typesTable());
            $table->configuredNullableMorphs('subjectable');
            $table->string('guest_key')->nullable();   // server-issued pseudonymous key; exactly one of subject or guest_key
            $table->string('status');                  // ConsentStatus enum: granted, denied, withdrawn
            $table->string('source');                  // which form, api client, or import produced the record
            $table->foreignId('policy_version_id')->nullable()->constrained($this->versionsTable());
            $table->string('policy_version')->nullable(); // denormalized for export readability ("Policy 1.0"); null = file-served text
            $table->string('ip_hash')->nullable();     // sha256 with app-level salt, jurisdiction dependent
            $table->timestamp('recorded_at')->index(); // utc; append-only rows carry no created_at/updated_at
            $table->index(['subjectable_type', 'subjectable_id', 'consent_type_id', 'recorded_at'], 'ai_consent_records_subject_lookup');
            $table->index(['guest_key', 'consent_type_id', 'recorded_at'], 'ai_consent_records_guest_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return (string) config('laranail.ai-compliance.tables.consent_records', 'ai_consent_records');
    }

    private function typesTable(): string
    {
        return (string) config('laranail.ai-compliance.tables.consent_types', 'ai_consent_types');
    }

    private function versionsTable(): string
    {
        return (string) config('laranail.ai-compliance.tables.policy_versions', 'ai_policy_versions');
    }
};
