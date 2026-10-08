<x-guest-layout>
    <h1 class="page-title text-center">Masuk ke akun admin</h1>
    <p class="mt-2 text-center text-sm text-text-muted">Gunakan kredensial admin untuk melanjutkan.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-2 block text-[13px] font-semibold">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm text-text placeholder:text-text-muted/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="nama@contoh.com"
                @if ($errors->has('email')) aria-describedby="email-error" @endif
            >
            @error('email')
                <p id="email-error" class="mt-2 text-[13px] text-danger" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-2 block text-[13px] font-semibold">Kata sandi</label>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm text-text placeholder:text-text-muted/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Masukkan kata sandi"
            >
        </div>

        <label for="remember" class="flex min-h-10 items-center gap-2 text-sm text-text-muted">
            <input id="remember" name="remember" type="checkbox" value="1" class="size-4 rounded-sm border-border-strong text-primary focus:ring-primary">
            Ingat saya
        </label>

        <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-md bg-primary px-4 text-sm font-semibold text-on-primary hover:bg-primary-hover">
            Masuk
        </button>
    </form>
</x-guest-layout>
