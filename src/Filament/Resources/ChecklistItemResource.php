<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Resources;

use Override;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Simtabi\Laranail\AiCompliance\Enums\CheckStatus;
use Simtabi\Laranail\AiCompliance\Checks\CheckRunner;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ChecklistItems\Pages\ListChecklistItems;

/**
 * The compliance checklist: statuses at a glance, manual evidence per item,
 * and the check runner one click away. Auto items are read-only here — the
 * runner owns them.
 *
 * @extends resource<ChecklistItem>
 */
final class ChecklistItemResource extends Resource
{
    protected static ?string $model = ChecklistItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Compliance checklist';

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable()->sortable(),
                TextColumn::make('section')->badge(),
                TextColumn::make('label')->wrap()->limit(80),
                TextColumn::make('status')->badge()->color(fn (CheckStatus $state): string => match ($state) {
                    CheckStatus::Ok            => 'success',
                    CheckStatus::Review        => 'warning',
                    CheckStatus::Fail          => 'danger',
                    CheckStatus::NotApplicable => 'gray',
                }),
                TextColumn::make('evidence_type'),
                TextColumn::make('last_verified_at')->dateTime()->placeholder('never'),
                IconColumn::make('stale')->label('Stale')->boolean()
                    ->state(fn (ChecklistItem $record): bool => $record->isStale()),
            ])
            ->filters([
                SelectFilter::make('section')->options(fn (): array => ChecklistItem::query()
                    ->distinct()->pluck('section', 'section')->all()),
                SelectFilter::make('status')->options([
                    'ok'     => 'ok',
                    'review' => 'review',
                    'fail'   => 'fail',
                    'na'     => 'n/a',
                ]),
            ])
            ->recordActions([
                Action::make('verify')
                    ->label('Record evidence')
                    ->visible(fn (ChecklistItem $record): bool => $record->evidence_type === 'manual')
                    ->schema([
                        Textarea::make('evidence_ref')->required()->maxLength(2000),
                    ])
                    ->action(function (ChecklistItem $record, array $data): void {
                        $verifiedBy = auth()->user()?->getAuthIdentifier();

                        $record->update([
                            'status'           => CheckStatus::Ok,
                            'evidence_ref'     => (string) $data['evidence_ref'],
                            'last_verified_at' => now(),
                            'verified_by'      => $verifiedBy !== null ? (string) $verifiedBy : 'admin',
                        ]);

                        Notification::make()->title('evidence recorded')->success()->send();
                    }),
            ])
            ->headerActions([
                Action::make('run_checks')
                    ->label('Run checks now')
                    ->action(function (): void {
                        $results = app(CheckRunner::class)->run();

                        Notification::make()
                            ->title(sprintf('%d checks ran', count($results)))
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('id');
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListChecklistItems::route('/'),
        ];
    }

    #[Override]
    public static function canCreate(): bool
    {
        return false;
    }
}
