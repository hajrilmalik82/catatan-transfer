<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'permissions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /*
     | Tanpa pemeriksaan ini Filament menolak semua orang begitu APP_ENV
     | bukan 'local' — itulah yang membuat panel 403 di hosting padahal
     | lolos di komputer sendiri. Aturannya menumpang izin yang sudah ada:
     | siapa pun yang punya setidaknya satu menu boleh masuk, dan admin
     | selalu lolos karena bolehAkses() meluluskannya lebih dulu.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->menuPertama() !== null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Admin selalu diizinkan. Pengguna lain diperiksa terhadap daftar hak
     * akses yang tersimpan, misalnya {"transaksi": ["lihat", "tambah"]}.
     */
    public function bolehAkses(string $menu, string $aksi = 'lihat'): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($aksi, $this->permissions[$menu] ?? [], true);
    }

    /** Menu pertama yang boleh dibuka, untuk menentukan halaman pendaratan. */
    public function menuPertama(): ?string
    {
        foreach (array_keys(config('catatan.menu')) as $menu) {
            if ($this->bolehAkses($menu)) {
                return $menu;
            }
        }

        return null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }
}
