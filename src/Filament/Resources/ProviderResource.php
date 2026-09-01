<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;
use Simtabi\Laranail\AiCompliance\Filament\Resources\Providers\Pages\CreateProvider;
use Simtabi\Laranail\AiCompliance\Filament\Resources\Providers\Pages\EditProvider;
use Simtabi\Laranail\AiCompliance\Filament\Resources\Providers\Pages\ListProviders;
use Simtabi\Laranail\AiCompliance\Models\Provider;

/**
 * The ai provider registry: the inventory rule as a crud surface. Deletes
 * are soft so the activity log keeps its references.
 *
 * @extends resource<Provider>
 */
final class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'AI providers';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('vendor')->required()->maxLength(255),
            TextInput::make('model_name')->required()->maxLength(255),
            TextInput::make('model_version')->maxLength(255),
            TextInput::make('endpoint_region')->maxLength(255),
            Select::make('role')->options(['provider' => 'provider', 'deployer' => 'deployer'])->required(),
            Textarea::make('purpose')->maxLength(2000)->columnSpanFull(),
            DateTimePicker::make('dpa_signed_at'),
            Select::make('trains_on_our_data')
                ->options(['yes' => 'yes', 'no' => 'no', 'configurable' => 'configurable'])
                ->required(),
            TextInput::make('training_summary_url')->url()->maxLength(2000),
            TextInput::make('sub_processors_url')->url()->maxLength(2000),
            Toggle::make('marking_supported'),
            Select::make('due_diligence_status')
                ->options(['pending' => 'pending', 'complete' => 'complete', 'lapsed' => 'lapsed'])
                ->default('pending'),
            TextInput::make('owner')->maxLength(255),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('vendor')->sortable(),
                TextColumn::make('model_name'),
                TextColumn::make('role')->badge(),
                TextColumn::make('trains_on_our_data')->badge(),
                TextColumn::make('due_diligence_status')->badge(),
                IconColumn::make('marking_supported')->boolean(),
                TextColumn::make('dpa_signed_at')->dateTime()->placeholder('—'),
            ])
            ->defaultSort('name');
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListProviders::route('/'),
            'create' => CreateProvider::route('/create'),
            'edit' => EditProvider::route('/{record}/edit'),
        ];
    }
}
