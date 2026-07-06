<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// the whole ai-compliance schema in one migration, ordered by dependency.
// table names come from config('laranail.ai-compliance.tables.*') — rename
// before running migrations, never after. tenant_id is NOT NULL with ''
// as the single-tenant default: composite unique indexes over a nullable
// column never fire (sql NULLs do not collide), so the sentinel is what
// makes (tenant_id, slug)-style constraints actually constrain.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->table('policy_documents'), function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->default('')->index();
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

        Schema::create($this->table('policy_versions'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('policy_document_id')->constrained($this->table('policy_documents'));
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

        Schema::create($this->table('policy_translations'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('policy_version_id')->constrained($this->table('policy_versions'));
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

        Schema::create($this->table('consent_types'), function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');              // canonical name for exports; display labels come from translations
            $table->text('description')->nullable();
            $table->string('legal_basis');        // LegalBasis enum: consent, legitimate_interest, contract
            $table->string('default_state');      // ConsentStatus enum; denied when the basis is consent
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create($this->table('consent_records'), function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();       // the only id exports and webhooks ever emit
            $table->string('tenant_id')->default('')->index();
            $table->foreignId('consent_type_id')->constrained($this->table('consent_types'));
            $table->configuredNullableMorphs('subjectable');
            $table->string('guest_key')->nullable();   // server-issued pseudonymous key; exactly one of subject or guest_key
            $table->string('status');                  // ConsentStatus enum: granted, denied, withdrawn
            $table->string('source');                  // which form, api client, or import produced the record
            $table->foreignId('policy_version_id')->nullable()->constrained($this->table('policy_versions'));
            $table->string('policy_version')->nullable(); // denormalized for export readability ("Policy 1.0"); null = file-served text
            $table->string('ip_hash')->nullable();     // sha256 with app-level salt, jurisdiction dependent
            $table->timestamp('recorded_at')->index(); // utc; append-only rows carry no created_at/updated_at
            $table->index(
                ['subjectable_type', 'subjectable_id', 'consent_type_id', 'recorded_at'],
                $this->table('consent_records') . '_subject_lookup',
            );
            $table->index(
                ['guest_key', 'consent_type_id', 'recorded_at'],
                $this->table('consent_records') . '_guest_lookup',
            );
        });

        // providers before activity events so the log can carry a real
        // foreign key. providers soft-delete (deactivated vendors stay
        // referenceable); a hard delete nulls the reference instead of
        // orphaning or erasing history.
        Schema::create($this->table('providers'), function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->default('')->index();
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
            $table->softDeletes();
        });

        Schema::create($this->table('activity_events'), function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();       // same export rule as consent records
            $table->string('tenant_id')->default('')->index();
            $table->string('event_type');
            $table->configuredNullableMorphs('actorable');   // who acted; null for system/scheduler events
            $table->configuredNullableMorphs('subjectable'); // who it was about
            $table->foreignId('provider_id')->nullable()->constrained($this->table('providers'))->nullOnDelete();
            $table->json('context')->nullable();       // no raw prompts or sensitive content
            $table->string('hash_prev', 64)->nullable(); // tamper-evidence chain
            $table->timestamp('recorded_at')->index(); // the single index serves retention pruning
            $table->index(['event_type', 'recorded_at']); // the admin read filters exactly this pair
            $table->index('provider_id');              // postgres does not index fk columns implicitly
        });

        Schema::create($this->table('checklist_items'), function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->default('')->index();
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

        Schema::create($this->table('classification_answers'), function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->default('')->index();
            $table->string('question_key');
            $table->string('answer');
            $table->string('answered_by');
            $table->timestamp('answered_at');
            $table->timestamps();
            $table->unique(['tenant_id', 'question_key']);
        });

        Schema::create($this->table('feature_states'), function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->default('')->index();
            $table->string('feature');
            $table->boolean('enabled')->default(false);
            $table->string('toggled_by')->nullable();
            $table->timestamp('toggled_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'feature']);
        });
    }

    public function down(): void
    {
        // reverse dependency order: referencing tables first
        Schema::dropIfExists($this->table('feature_states'));
        Schema::dropIfExists($this->table('classification_answers'));
        Schema::dropIfExists($this->table('checklist_items'));
        Schema::dropIfExists($this->table('activity_events'));
        Schema::dropIfExists($this->table('providers'));
        Schema::dropIfExists($this->table('consent_records'));
        Schema::dropIfExists($this->table('consent_types'));
        Schema::dropIfExists($this->table('policy_translations'));
        Schema::dropIfExists($this->table('policy_versions'));
        Schema::dropIfExists($this->table('policy_documents'));
    }

    private function table(string $key): string
    {
        $default = 'ai_' . $key;

        return (string) config('laranail.ai-compliance.tables.' . $key, $default);
    }
};
