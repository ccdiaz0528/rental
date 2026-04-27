<?php

namespace App\Filament\Resources\PagoDiarios;

use App\Filament\Resources\PagoDiarios\Pages\CreatePagoDiario;
use App\Filament\Resources\PagoDiarios\Pages\EditPagoDiario;
use App\Filament\Resources\PagoDiarios\Pages\ListPagoDiarios;
use App\Filament\Resources\PagoDiarios\Pages\ViewPagoDiario;
use App\Filament\Resources\PagoDiarios\Schemas\PagoDiarioForm;
use App\Filament\Resources\PagoDiarios\Schemas\PagoDiarioInfolist;
use App\Filament\Resources\PagoDiarios\Tables\PagoDiariosTable;
use App\Models\PagoDiario;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PagoDiarioResource extends Resource
{
    protected static ?string $model = PagoDiario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return PagoDiarioForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PagoDiarioInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagoDiariosTable::configure($table);
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
            'index' => ListPagoDiarios::route('/'),
            'create' => CreatePagoDiario::route('/create'),
            'view' => ViewPagoDiario::route('/{record}'),
            'edit' => EditPagoDiario::route('/{record}/edit'),
        ];
    }
}
