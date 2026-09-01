<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Resources\PolicyDocuments\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Override;
use Simtabi\Laranail\AiCompliance\Filament\Resources\PolicyDocumentResource;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Models\PolicyTranslation;
use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyDrafts;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyPublisher;

/**
 * The policy editor: the markdown of the document's open draft (or, before
 * one exists, the latest version — saving opens the draft), stored
 * byte-for-byte through the shared PolicyDrafts service, with publish as an
 * explicit header action that supersedes atomically.
 *
 * @property PolicyDocument $record
 */
final class EditPolicyDocument extends EditRecord
{
    protected static string $resource = PolicyDocumentResource::class;

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            MarkdownEditor::make('source_markdown')
                ->label('Markdown (default locale, frontmatter included)')
                ->required()
                ->columnSpanFull(),
        ]);
    }

    #[Override]
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $translation = $this->currentTranslation();

        $data['source_markdown'] = $translation instanceof PolicyTranslation
            ? $translation->source_markdown
            : '';

        return $data;
    }

    #[Override]
    protected function handleRecordUpdate(Model $record, array $data): PolicyDocument
    {
        assert($record instanceof PolicyDocument);

        $drafts = app(PolicyDrafts::class);

        $draft = $drafts->openDraft($record);
        $drafts->updateTranslation($draft, $record->default_locale, (string) $data['source_markdown']);

        return $record;
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label('Publish draft')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->draftVersion()->exists())
                ->action(function (): void {
                    /** @var PolicyVersion $draft */
                    $draft = $this->record->draftVersion()->firstOrFail();

                    $published = app(PolicyPublisher::class)->publish($draft, auth()->user());

                    Notification::make()
                        ->title(sprintf('published %s %s', $this->record->slug, $published->version))
                        ->success()
                        ->send();

                    $this->refreshFormData(['source_markdown']);
                }),
        ];
    }

    /**
     * What the editor shows: the open draft's default-locale translation,
     * else the latest version's.
     */
    private function currentTranslation(): ?PolicyTranslation
    {
        /** @var PolicyVersion|null $version */
        $version = $this->record->draftVersion()->first() ?? $this->record->latestVersion()->first();

        if ($version === null) {
            return null;
        }

        /** @var PolicyTranslation|null $translation */
        $translation = $version->translations()
            ->where('locale', $this->record->default_locale)
            ->first();

        return $translation;
    }
}
