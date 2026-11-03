<?php

namespace App\Filament\Resources\Recipients;

use App\Filament\Resources\Recipients\Pages\ManageRecipients;
use App\Models\Recipient;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class RecipientResource extends Resource
{
    protected static ?string $model = Recipient::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Penerima';

    protected static ?string $modelLabel = 'Penerima';

    protected static ?string $pluralModelLabel = 'Penerima';

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'nama';

    private const MENU = 'penerima';

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
                ->label('Nama Penerima')
                ->required()
                ->maxLength(255),

            Select::make('bank')
                ->label('Bank')
                ->options(config('catatan.banks'))
                ->searchable()
                ->required(),

            TextInput::make('nomor_rekening')
                ->label('Nomor Rekening')
                ->required()
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
                TextColumn::make('bank')->label('Bank')->searchable(),
                TextColumn::make('nomor_rekening')->label('No. Rekening')->searchable(),
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
            'index' => ManageRecipients::route('/'),
        ];
    }
}
