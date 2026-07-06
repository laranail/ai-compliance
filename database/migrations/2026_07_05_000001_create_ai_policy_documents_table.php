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
            $table->string('tenant_id')->nullable()->index();
            $table->string('slug');
            $table->string('type');                          // PolicyType enum: policy, consent_text, disclosure
            $table->string('surface')->nullable();           // disclosure documents: chat, content, decision
            $table->string('consent_type_slug')->nullable(); // consent_text documents
            $table->string('source_path')->nullable();       // shipped md file this was imported from
            $table->string('default_locale');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return (string) config('laranail.ai-compliance.tables.policy_documents', 'ai_policy_documents');
    }
};
