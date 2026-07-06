<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Override;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ConsentRecords\Pages\ListConsentRecords;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;

/**
 * The consent log, strictly read-only: append-only rows are history, and
 * the table emits public ids only. The csv export action ships with the
 * exports milestone.
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
                    'granted' => 'granted',
                    'denied' => 'denied',
                    'withdrawn' => 'withdrawn',
                ]),
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
