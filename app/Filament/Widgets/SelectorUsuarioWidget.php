<?php

namespace App\Filament\Widgets;

use App\Concerns\HasUserContext;
use Filament\Widgets\Widget;

class SelectorUsuarioWidget extends Widget
{
    use HasUserContext;

    protected static ?int $sort = 0;

    protected static bool $isDiscovered = true;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.selector-usuario';

    public function isAdmin(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }
}
