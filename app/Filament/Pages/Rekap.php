<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\RekapData;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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

    protected function getHeaderActions(): array
    {
        $aktif = Setting::get('rekap_publik_aktif', true);

        return [
            Action::make('togglePublik')
                ->label($aktif ? 'Tampilan Publik: Aktif' : 'Tampilan Publik: Nonaktif')
                ->color($aktif ? 'success' : 'danger')
                ->icon($aktif ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading($aktif ? 'Nonaktifkan Tampilan Publik?' : 'Aktifkan Tampilan Publik?')
                ->modalDescription($aktif
                    ? 'Jika dinonaktifkan, pengunjung umum tanpa login tidak dapat mengakses halaman rekap atau mengunduh berkas.'
                    : 'Jika diaktifkan, link publik /rekap dapat dibuka oleh siapa saja tanpa login.')
                ->modalSubmitActionLabel($aktif ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan')
                ->action(function () {
                    $baru = ! Setting::get('rekap_publik_aktif', true);
                    Setting::set('rekap_publik_aktif', $baru);

                    Notification::make()
                        ->title($baru ? 'Tampilan publik berhasil diaktifkan' : 'Tampilan publik berhasil dinonaktifkan')
                        ->success()
                        ->send();
                }),

            Action::make('bukaPublik')
                ->label('Buka Link Publik')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(route('rekap.public'), shouldOpenInNewTab: true),
        ];
    }

    protected function getViewData(): array
    {
        return array_merge(
            app(RekapData::class)->build(request()),
            ['publikAktif' => Setting::get('rekap_publik_aktif', true)]
        );
    }
}
