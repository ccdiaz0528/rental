<?php

namespace App\Filament\Resources\Deudas;

use App\Filament\Resources\Deudas\Pages\CreateDeuda;
use App\Filament\Resources\Deudas\Pages\ListDeudas;
use App\Filament\Resources\Deudas\Schemas\DeudaForm;
use App\Filament\Resources\Deudas\Tables\DeudasTable;
use App\Models\Deuda;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DeudaResource extends Resource
{
    protected static ?string $model = Deuda::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Cartera';

    protected static ?string $modelLabel = 'Deuda';

    protected static ?string $pluralModelLabel = 'Cartera';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return DeudaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeudasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeudas::route('/'),
            'create' => CreateDeuda::route('/create'),
        ];
    }
}
