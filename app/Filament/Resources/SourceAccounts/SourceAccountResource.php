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
use Illuminate\Database\Eloquent\Model;
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

    private const MENU = 'rekening_sumber';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->bolehAkses(self::MENU) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->bolehAkses(self::MENU) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->bolehAkses(self::MENU, 'lihat') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->bolehAkses(self::MENU, 'tambah') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->bolehAkses(self::MENU, 'ubah') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->bolehAkses(self::MENU, 'hapus') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->bolehAkses(self::MENU, 'hapus') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama Rekening Sumber')
                ->helperText('Ketik bebas, misalnya nama channel atau rekening pengirim.')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('nomor_rekening')
                ->label('Nomor Rekening')
                ->helperText('Dipakai untuk mencocokkan bukti transfer saat import. Lihat baris "Sumber Dana" atau "Source of Fund" di PDF.')
                ->unique(ignoreRecord: true)
                ->maxLength(50),

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
                TextColumn::make('nomor_rekening')
                    ->label('No. Rekening')
                    ->placeholder('belum diisi')
                    ->searchable(),
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
