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
            $table->string('slug')->unique();
            $table->string('label');              // canonical name for exports; display labels come from translations
            $table->text('description')->nullable();
            $table->string('legal_basis');        // LegalBasis enum: consent, legitimate_interest, contract
            $table->string('default_state');      // ConsentStatus enum; denied when the basis is consent
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return (string) config('laranail.ai-compliance.tables.consent_types', 'ai_consent_types');
    }
};
