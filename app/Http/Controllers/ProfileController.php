<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

// H11: shared profile (all roles). Self-service column lock enforced via UpdateProfileRequest.
// email/picture/cover editable by all; name editable by educators/admins only (students RO).
class ProfileController extends Controller
{
    // ponytail: profile photos on the public disk — guessable but low-sensitivity; signed route if that ever matters.
    private const DISK = 'profile_media';

    public function edit(): View
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function media(string $path): Response|StreamedResponse|ResponseFactory
    {
        abort_if(str_contains($path, '..'), 404);
        abort_unless(Storage::disk(self::DISK)->exists($path), 404);

        return Storage::disk(self::DISK)->response($path);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        // email is not editable here — it is changed only via the Google account flow (see OAuthController).
        // name only present for non-students (Form Request drops it for students).
        if (array_key_exists('given_name', $data)) {
            $user->given_name = $data['given_name'];
            $user->surname = $data['surname'];
        }

        if ($request->hasFile('profile_picture')) {
            $this->replaceMedia($user, 'profile_picture', $request->file('profile_picture'));
        } elseif ($request->boolean('remove_profile_picture')) {
            $this->deleteMedia($user, 'profile_picture');
        }
        if ($request->hasFile('cover_photo')) {
            $this->replaceMedia($user, 'cover_photo', $request->file('cover_photo'));
        } elseif ($request->boolean('remove_cover_photo')) {
            $this->deleteMedia($user, 'cover_photo');
        }

        $user->save();

        return redirect()->route('profile.edit')->with('status', 'Profile settings updated successfully.');
    }

    public function updatePassword(): RedirectResponse
    {
        // New + confirm only — no current-password prompt (per the profile spec).
        $data = request()->validate([
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = Auth::user();
        $user->password = $data['password']; // 'hashed' cast on the model hashes it
        $user->save();

        return redirect()->route('profile.edit')->with('status', 'Password updated successfully.');
    }

    private function replaceMedia($user, string $column, $file): void
    {
        // delete the old object if it lived on our disk.
        if ($user->$column && Storage::disk(self::DISK)->exists($user->$column)) {
            Storage::disk(self::DISK)->delete($user->$column);
        }
        $user->$column = $file->store((string) $user->id, self::DISK);
    }

    private function deleteMedia($user, string $column): void
    {
        if ($user->$column && Storage::disk(self::DISK)->exists($user->$column)) {
            Storage::disk(self::DISK)->delete($user->$column);
        }
        $user->$column = null;
    }
}
