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
            $table->foreignId('policy_version_id')->constrained($this->versionsTable());
            $table->string('locale');
            $table->string('title');
            $table->longText('source_markdown');            // raw markdown as authored (frontmatter body included)
            $table->longText('compiled_html');              // shortcodes compiled, placeholders left intact
            $table->json('meta')->nullable();               // parsed frontmatter: short text for consent docs, ...
            $table->char('checksum', 64);                   // sha256 of source_markdown
            $table->char('file_checksum', 64)->nullable();  // sha256 of the shipped file at import (file-drift anchor)
            $table->char('origin_checksum', 64)->nullable(); // default-locale checksum this translation was made from
            $table->timestamps();
            $table->unique(['policy_version_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return (string) config('laranail.ai-compliance.tables.policy_translations', 'ai_policy_translations');
    }

    private function versionsTable(): string
    {
        return (string) config('laranail.ai-compliance.tables.policy_versions', 'ai_policy_versions');
    }
};
