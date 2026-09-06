<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Resources;

use Override;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Filament\Resources\PolicyDocuments\Pages\EditPolicyDocument;
use Simtabi\Laranail\AiCompliance\Filament\Resources\PolicyDocuments\Pages\ListPolicyDocuments;

/**
 * The policy documents: list with version state, and the markdown editor
 * (EditPolicyDocument) that edits the open draft through the shared
 * PolicyDrafts service.
 *
 * @extends resource<PolicyDocument>
 */
final class PolicyDocumentResource extends Resource
{
    protected static ?string $model = PolicyDocument::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'AI policies';

    protected static ?string $modelLabel = 'policy document';

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')->searchable()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('publishedVersion.version')->label('Published'),
                TextColumn::make('draftVersion.version')->label('Open draft')->placeholder('—'),
                IconColumn::make('active')->boolean(),
            ])
            ->defaultSort('slug');
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListPolicyDocuments::route('/'),
            'edit'  => EditPolicyDocument::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function canCreate(): bool
    {
        return false; // documents come from the shipped files via the sync
    }
}
