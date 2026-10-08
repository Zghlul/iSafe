<?php

namespace App\Http\Controllers;

use App\Models\PhoneModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PhoneModelController extends Controller
{
    public function index(): View
    {
        return view('models.index', ['models' => PhoneModel::query()->orderBy('name')->paginate(25)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', 'unique:phone_models,name']]);
        PhoneModel::query()->create($data);

        return back()->with('success', 'Model iPhone berhasil ditambahkan.');
    }

    public function update(Request $request, PhoneModel $phoneModel): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', Rule::unique('phone_models', 'name')->ignore($phoneModel->id)]]);
        $phoneModel->update($data);

        return back()->with('success', 'Model iPhone berhasil diperbarui.');
    }

    public function destroy(PhoneModel $phoneModel): RedirectResponse
    {
        $isUsed = \App\Models\Sale::query()->where('model', $phoneModel->name)->exists();

        if ($isUsed) {
            return back()->with('error', 'Model ini sudah dipakai pada transaksi dan tidak dapat dihapus.');
        }

        $phoneModel->delete();

        return back()->with('success', 'Model iPhone dihapus dari pilihan transaksi.');
    }
}
