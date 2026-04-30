<?php

namespace App\Filament\Resources\Contratos;

use App\Filament\Resources\Contratos\Pages\CreateContrato;
use App\Filament\Resources\Contratos\Pages\EditContrato;
use App\Filament\Resources\Contratos\Pages\ListContratos;
use App\Filament\Resources\Contratos\Pages\ViewContrato;
use App\Filament\Resources\Contratos\Schemas\ContratoForm;
use App\Filament\Resources\Contratos\Schemas\ContratoInfolist;
use App\Filament\Resources\Contratos\Tables\ContratosTable;
use App\Models\Contrato;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ContratoResource extends Resource
{
    protected static ?string $model = Contrato::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Contratos';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return ContratoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContratoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContratosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContratos::route('/'),
            'create' => CreateContrato::route('/create'),
            'view' => ViewContrato::route('/{record}'),
            'edit' => EditContrato::route('/{record}/edit'),
        ];
    }
}
