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
            $table->string('key')->index();
            $table->string('section');
            $table->string('label');
            $table->text('description')->nullable();
            $table->json('applies_when')->nullable();     // rules against classification answers
            $table->string('status')->default('review');  // CheckStatus enum: ok, review, fail, na
            $table->string('evidence_type');              // auto or manual
            $table->text('evidence_ref')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->string('verified_by')->nullable();
            $table->unsignedSmallInteger('staleness_months')->default(12);
            $table->timestamps();
            $table->unique(['tenant_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return (string) config('laranail.ai-compliance.tables.checklist_items', 'ai_checklist_items');
    }
};
