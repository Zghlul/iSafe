<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index', ['settings' => Setting::query()->pluck('value', 'key')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'store_address' => ['nullable', 'string', 'max:500'],
            'monthly_target' => ['nullable', 'integer', 'min:0'],
            'store_logo' => ['nullable', 'image', 'max:2048'],
        ]);
        $oldLogo = Setting::query()->where('key', 'store_logo')->value('value');
        unset($data['store_logo']);
        if ($request->hasFile('store_logo')) {
            $path = $request->file('store_logo')->store('store', 'public');
            $data['store_logo'] = $path;
        }
        foreach ($data as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        }

        if ($request->hasFile('store_logo') && $oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        return back()->with('success', 'Pengaturan usaha berhasil disimpan.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
