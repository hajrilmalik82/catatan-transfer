<?php

namespace App\Filament\Pages;

use App\Support\RekapData;
use BackedEnum;
use Filament\Pages\Page;

class Rekap extends Page
{
    protected string $view = 'filament.pages.rekap';

    protected static ?string $title = 'Rekap Transfer';

    protected static ?string $navigationLabel = 'Rekap';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->bolehAkses('rekap') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->bolehAkses('rekap') ?? false;
    }

    protected function getViewData(): array
    {
        return app(RekapData::class)->build(request());
    }
}
