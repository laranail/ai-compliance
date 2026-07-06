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
            $table->foreignId('policy_document_id')->constrained($this->documentsTable());
            $table->string('version');                    // '1.0', '1.1', auto-bumped
            $table->string('status')->default('draft');   // PolicyVersionStatus enum
            $table->configuredNullableMorphs('authorable'); // who created/published; null = seeder or sync
            $table->timestamp('effective_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();
            $table->unique(['policy_document_id', 'version']);
            $table->index(['policy_document_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return (string) config('laranail.ai-compliance.tables.policy_versions', 'ai_policy_versions');
    }

    private function documentsTable(): string
    {
        return (string) config('laranail.ai-compliance.tables.policy_documents', 'ai_policy_documents');
    }
};
