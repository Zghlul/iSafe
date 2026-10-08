@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
<div>
    <div class="mb-5">
        <p class="mb-2 text-xs font-semibold text-text-muted">Kelola / Pengaturan</p>
        <h1 class="page-title">Pengaturan</h1>
        <p class="mt-2 text-sm text-text-muted">Profil usaha, target omzet, dan keamanan akun.</p>
    </div>

    @if (session('success'))<div class="mb-4 rounded-md border border-success bg-success-soft px-4 py-3 text-sm text-success" role="status">{{ session('success') }}</div>@endif

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <x-app.card>
            <h2 class="card-title">Profil usaha</h2>
            <p class="mt-1 text-xs text-text-muted">Informasi yang ditampilkan di aplikasi dan laporan.</p>
            <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf @method('PUT')
                <div>
                    <label for="store-name" class="mb-2 block text-[13px] font-semibold">Nama toko</label>
                    <input id="store-name" name="store_name" value="{{ old('store_name', $settings['store_name'] ?? config('app.name')) }}" required maxlength="120" class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                    @error('store_name')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="store-address" class="mb-2 block text-[13px] font-semibold">Alamat</label>
                    <textarea id="store-address" name="store_address" rows="3" maxlength="500" class="w-full rounded-md border border-border-strong bg-surface px-3 py-2 text-sm">{{ old('store_address', $settings['store_address'] ?? '') }}</textarea>
                    @error('store_address')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="monthly-target" class="mb-2 block text-[13px] font-semibold">Target omzet bulanan (Rp)</label>
                    <input id="monthly-target" name="monthly_target" type="number" min="0" step="1" value="{{ old('monthly_target', $settings['monthly_target'] ?? '') }}" inputmode="numeric" class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm tabular-nums">
                    @error('monthly_target')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="store-logo" class="mb-2 block text-[13px] font-semibold">Logo usaha</label>
                    <input id="store-logo" name="store_logo" type="file" accept="image/png,image/jpeg,image/webp" class="block min-h-11 w-full rounded-md border border-border-strong bg-surface px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-text-muted">PNG, JPG, atau WebP; maksimal 2 MB.</p>
                    @error('store_logo')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-primary px-5 text-sm font-semibold text-on-primary">Simpan profil</button>
            </form>
        </x-app.card>

        <x-app.card>
            <h2 class="card-title">Ubah kata sandi</h2>
            <p class="mt-1 text-xs text-text-muted">Gunakan kata sandi kuat yang tidak dipakai di akun lain.</p>
            <form action="{{ route('settings.password') }}" method="POST" class="mt-5 space-y-4">
                @csrf @method('PUT')
                <div>
                    <label for="current-password" class="mb-2 block text-[13px] font-semibold">Kata sandi saat ini</label>
                    <input id="current-password" name="current_password" type="password" required autocomplete="current-password" class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                    @error('current_password', 'updatePassword')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="new-password" class="mb-2 block text-[13px] font-semibold">Kata sandi baru</label>
                    <input id="new-password" name="password" type="password" required autocomplete="new-password" class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                    @error('password', 'updatePassword')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password-confirmation" class="mb-2 block text-[13px] font-semibold">Konfirmasi kata sandi baru</label>
                    <input id="password-confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                </div>
                <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-primary px-5 text-sm font-semibold text-on-primary">Perbarui kata sandi</button>
            </form>
        </x-app.card>
    </div>
</div>
@endsection
