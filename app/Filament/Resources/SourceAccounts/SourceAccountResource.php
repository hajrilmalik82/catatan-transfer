<?php

namespace App\Filament\Resources\SourceAccounts;

use App\Filament\Resources\SourceAccounts\Pages\ManageSourceAccounts;
use App\Models\SourceAccount;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SourceAccountResource extends Resource
{
    protected static ?string $model = SourceAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Rekening Sumber';

    protected static ?string $modelLabel = 'Rekening Sumber';

    protected static ?string $pluralModelLabel = 'Rekening Sumber';

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama Rekening Sumber')
                ->helperText('Ketik bebas, misalnya nama channel atau rekening pengirim.')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Toggle::make('aktif')
                ->label('Aktif')
                ->helperText('Nonaktifkan kalau sudah tidak dipakai. Data lama tetap tersimpan.')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                TextColumn::make('transactions_count')
                    ->label('Jumlah Transaksi')
                    ->counts('transactions'),
                IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->defaultSort('nama')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSourceAccounts::route('/'),
        ];
    }
}
