<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Resources\Transactions\Pages\ManageTransactions;
use App\Models\Transaction;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transaksi';

    protected static ?string $modelLabel = 'Transaksi';

    protected static ?string $pluralModelLabel = 'Transaksi';

    protected static ?int $navigationSort = 1;

    private const MENU = 'transaksi';

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
            DatePicker::make('tanggal')
                ->label('Tanggal')
                ->native(false)
                ->default(now())
                ->required(),

            Select::make('source_account_id')
                ->label('Rekening Sumber')
                ->relationship('sourceAccount', 'nama', fn (Builder $query) => $query->where('aktif', true))
                ->searchable()
                ->preload()
                ->required(),

            Select::make('recipient_id')
                ->label('Penerima')
                ->relationship('recipient', 'nama', fn (Builder $query) => $query->where('aktif', true))
                ->searchable()
                ->preload()
                ->required(),

            TextInput::make('remark')
                ->label('Remark')
                ->helperText('Teks bebas. Nilai yang pernah dipakai muncul sebagai saran.')
                ->maxLength(255)
                ->datalist(fn () => Transaction::query()
                    ->whereNotNull('remark')
                    ->distinct()
                    ->orderBy('remark')
                    ->pluck('remark')
                    ->all()),

            TextInput::make('nominal_tagihan')
                ->label('Nominal Tagihan')
                ->helperText('Nilai penuh sesuai nota, sebelum potongan admin.')
                ->numeric()
                ->prefix('Rp')
                ->minValue(0)
                ->required(),

            TextInput::make('biaya_admin')
                ->label('Biaya Admin')
                ->helperText('Potongan admin dari bank untuk transaksi ini. Isi 0 kalau tidak ada potongan.')
                ->numeric()
                ->prefix('Rp')
                ->default(0)
                ->minValue(0)
                ->required(),

            TextInput::make('ref_transaksi')
                ->label('ID Transaksi Bank')
                ->helperText('Opsional. Disalin dari bukti transfer.')
                ->maxLength(255),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('sourceAccount.nama')
                    ->label('Sumber')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('recipient.nama')
                    ->label('Penerima')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('remark')
                    ->label('Remark')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('nominal_tagihan')
                    ->label('Tagihan')
                    ->money('IDR', locale: 'id')
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('biaya_admin')
                    ->label('Admin')
                    ->money('IDR', locale: 'id')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nominal_transfer')
                    ->label('Dikirim')
                    ->money('IDR', locale: 'id')
                    ->alignEnd()
                    ->sortable()
                    ->summarize(Sum::make()->money('IDR', locale: 'id')->label('Total')),

                TextColumn::make('ref_transaksi')
                    ->label('Ref Bank')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Filter::make('periode')
                    ->schema([
                        DatePicker::make('dari')->label('Dari tanggal')->native(false),
                        DatePicker::make('sampai')->label('Sampai tanggal')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari'] ?? null, fn (Builder $q, $date) => $q->whereDate('tanggal', '>=', $date))
                            ->when($data['sampai'] ?? null, fn (Builder $q, $date) => $q->whereDate('tanggal', '<=', $date));
                    }),

                SelectFilter::make('source_account_id')
                    ->label('Rekening Sumber')
                    ->relationship('sourceAccount', 'nama')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('recipient_id')
                    ->label('Penerima')
                    ->relationship('recipient', 'nama')
                    ->searchable()
                    ->preload(),

            ])
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
            'index' => ManageTransactions::route('/'),
        ];
    }
}
