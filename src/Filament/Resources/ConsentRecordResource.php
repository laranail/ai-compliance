<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Resources;

use Override;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\SelectFilter;
use Simtabi\Laranail\AiCompliance\Exports\LogExports;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ConsentRecords\Pages\ListConsentRecords;

/**
 * The consent log, strictly read-only: append-only rows are history, and
 * the table emits public ids only. The csv export runs through the same
 * pseudonymizing LogExports service as the http endpoint and requires the
 * dedicated export gate.
 *
 * @extends resource<ConsentRecord>
 */
final class ConsentRecordResource extends Resource
{
    protected static ?string $model = ConsentRecord::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Consent log';

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('public_id')->label('Id')->searchable(),
                TextColumn::make('type.slug')->label('Consent type'),
                TextColumn::make('status')->badge(),
                TextColumn::make('source'),
                TextColumn::make('policy_version')->placeholder('file'),
                TextColumn::make('recorded_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'granted'   => 'granted',
                    'denied'    => 'denied',
                    'withdrawn' => 'withdrawn',
                ]),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label('Export CSV')
                    ->visible(fn (): bool => auth()->user()?->can('ai-compliance:export') ?? false)
                    ->action(fn (): StreamedResponse => response()->streamDownload(
                        function (): void {
                            echo app(LogExports::class)
                                ->toCsv(app(LogExports::class)->consentRows());
                        },
                        'consent-log.csv',
                        ['Content-Type' => 'text/csv'],
                    )),
            ])
            ->defaultSort('recorded_at', 'desc');
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListConsentRecords::route('/'),
        ];
    }

    #[Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[Override]
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    #[Override]
    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
