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
            $table->string('name');
            $table->string('vendor');
            $table->string('model_name');
            $table->string('model_version')->nullable();
            $table->string('endpoint_region')->nullable();
            $table->string('role');                    // provider or deployer
            $table->text('purpose')->nullable();
            $table->timestamp('dpa_signed_at')->nullable();
            $table->string('trains_on_our_data');      // yes, no, configurable
            $table->string('training_summary_url')->nullable();
            $table->string('sub_processors_url')->nullable();
            $table->boolean('marking_supported')->default(false);
            $table->string('due_diligence_status')->default('pending'); // pending, complete, lapsed
            $table->string('owner')->nullable();
            $table->timestamps();
            $table->softDeletes();                     // deactivated vendors stay referenceable from the log
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return (string) config('laranail.ai-compliance.tables.providers', 'ai_providers');
    }
};
