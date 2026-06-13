<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'no_hp' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'data' => $user->fresh(),
            'message' => 'Profil berhasil diperbarui',
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'password_lama' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $user = $request->user();

        if (!Hash::check($request->password_lama, $user->password)) {
            return response()->json([
                'message' => 'Password lama tidak sesuai',
                'errors' => ['password_lama' => ['Password lama tidak sesuai']],
            ], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Password berhasil diubah']);
    }

    public function uploadFoto(Request $request): JsonResponse
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $user = $request->user();

        // Hapus foto lama jika ada
        if ($user->foto) {
            // Ekstrak path relatif dari URL apapun (bisa localhost, domain produksi, dll)
            $parsed = parse_url($user->foto);
            $oldPath = ltrim(str_replace('/storage/', '', $parsed['path'] ?? ''), '/');
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $path = $request->file('foto')->store('foto-profil', 'public');
        // Gunakan url() helper agar mengikuti host request yang sebenarnya
        // (bukan APP_URL di .env yang mungkin masih 'localhost')
        $url = url('storage/' . $path);

        $user->update(['foto' => $url]);

        return response()->json([
            'data' => ['foto' => $url],
            'message' => 'Foto profil berhasil diperbarui',
        ]);
    }
}
