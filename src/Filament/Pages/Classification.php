<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Simtabi\Laranail\AiCompliance\Support\RegisteredNames;
use Simtabi\Laranail\AiCompliance\Filament\Concerns\RedirectsLegacyPageSlug;
use Simtabi\Laranail\AiCompliance\Checklist\Classification as ClassificationService;

/**
 * The section-2 project intake: the answers switch checklist sections on or
 * off and are themselves evidence.
 *
 * @property-read Schema $form
 */
final class Classification extends Page
{
    // the pre-0.1 slug keeps answering, as a redirect (see RedirectsLegacyPageSlug)
    use RedirectsLegacyPageSlug;

    /**
     * The pre-0.1 slug, derived by Filament from the class name. Its URLs and route names
     * redirect to the scoped slug below.
     *
     * @deprecated use the `laranail-ai-compliance/classification` slug. Earliest
     *             removal: the next minor after 0.1.
     */
    public const string LEGACY_SLUG = 'classification';

    protected static ?string $slug = RegisteredNames::FILAMENT_SLUG_PREFIX . '/classification';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationLabel = 'AI classification';

    protected static ?string $title = 'AI classification intake';

    /** @var array<string, mixed> */
    public array $data = [];

    protected string $view = 'laranail/ai-compliance::filament.classification';

    public function mount(): void
    {
        $this->form->fill(app(ClassificationService::class)->answers());
    }

    public function form(Schema $schema): Schema
    {
        $yesNo = ['yes' => 'yes', 'no' => 'no'];

        return $schema
            ->components([
                Select::make('role')->options(['provider' => 'provider', 'deployer' => 'deployer', 'both' => 'both'])
                    ->label('Are we a provider, a deployer, or both?'),
                Select::make('interacts_with_people')->options($yesNo)
                    ->label('Does the system interact directly with people?'),
                Select::make('generates_synthetic_content')->options($yesNo)
                    ->label('Does it generate synthetic content?'),
                Select::make('consequential_decisions')->options($yesNo)
                    ->label('Does it make or materially influence consequential decisions?'),
                Select::make('processes_personal_data')->options($yesNo)
                    ->label('Does it process personal data?'),
                Select::make('biometrics_emotion')->options($yesNo)
                    ->label('Does it touch biometrics or emotion recognition?'),
                TextInput::make('markets')
                    ->label('Which markets? (eu, uk, us states, ...)'),
                Select::make('minors_plausible')->options($yesNo)
                    ->label('Are minors plausible users?'),
                Select::make('publishes_ai_content')->options($yesNo)
                    ->label('Is any AI content publicly published?'),
                Select::make('trains_on_collected_data')->options($yesNo)
                    ->label('Do we train or fine-tune on data we collect?'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $answers = [];

        foreach ($this->form->getState() as $key => $answer) {
            if (is_string($key) && is_string($answer) && $answer !== '') {
                $answers[$key] = $answer;
            }
        }

        $answeredBy = auth()->user()?->getAuthIdentifier();

        app(ClassificationService::class)->record($answers, $answeredBy !== null ? (string) $answeredBy : 'admin');

        Notification::make()
            ->title('classification recorded; the checklist has been re-derived')
            ->success()
            ->send();
    }
}
