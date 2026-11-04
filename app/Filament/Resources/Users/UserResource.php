<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 9;

    // Pengelolaan pengguna hanya untuk admin, bukan bagian dari hak akses
    // yang bisa diberikan ke staf.
    public static function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        if (! (auth()->user()?->isAdmin() ?? false)) {
            return Response::deny();
        }

        // Jangan sampai admin menghapus akunnya sendiri lalu terkunci di luar.
        if ($action === 'delete' && $record?->getKey() === auth()->id()) {
            return Response::deny();
        }

        return Response::allow();
    }

    public static function form(Schema $schema): Schema
    {
        $kotakHak = [];

        foreach (config('catatan.menu') as $kunci => $menu) {
            $kotakHak[] = CheckboxList::make('permissions.'.$kunci)
                ->label($menu['label'])
                ->options(collect($menu['aksi'])
                    ->mapWithKeys(fn (string $a) => [$a => config('catatan.label_aksi')[$a] ?? $a])
                    ->all())
                ->columns(4)
                ->visible(fn (callable $get): bool => $get('role') !== 'admin');
        }

        return $schema->components(array_merge([
            TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                ->helperText('Saat mengubah pengguna, kosongkan kalau passwordnya tidak diganti.')
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->maxLength(255),

            Select::make('role')
                ->label('Peran')
                ->options(['admin' => 'Admin (akses penuh)', 'staf' => 'Staf (hak akses diatur)'])
                ->default('staf')
                ->required()
                ->live()
                ->helperText('Admin bisa mengelola pengguna dan membuka semua menu.'),
        ], $kotakHak));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'admin' ? 'Admin' : 'Staf'),
                TextColumn::make('permissions')
                    ->label('Menu yang boleh dibuka')
                    ->formatStateUsing(function ($state, User $record): string {
                        if ($record->isAdmin()) {
                            return 'Semua menu';
                        }

                        $menu = config('catatan.menu');

                        return collect($state ?? [])
                            ->filter(fn ($aksi) => filled($aksi))
                            ->keys()
                            ->map(fn (string $k) => $menu[$k]['label'] ?? $k)
                            ->join(', ') ?: '-';
                    })
                    ->wrap(),
            ])
            ->defaultSort('name')
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
            'index' => ManageUsers::route('/'),
        ];
    }
}
