<?php

namespace App\Filament\Resources\SourceAccounts\Pages;

use App\Filament\Resources\SourceAccounts\SourceAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSourceAccounts extends ManageRecords
{
    protected static string $resource = SourceAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Rekening Sumber'),
        ];
    }
}
