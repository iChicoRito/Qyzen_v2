<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

// H11: shared profile (all roles). Self-service column lock enforced via UpdateProfileRequest.
// email/picture/cover editable by all; name editable by educators/admins only (students RO).
class ProfileController extends Controller
{
    private const DISK = 'local';

    public function edit(): View
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        // email is NOT in $fillable (self-service lock) — set explicitly. Allowed for self-email change.
        $user->email = $data['email'];

        // name only present for non-students (Form Request drops it for students).
        if (array_key_exists('given_name', $data)) {
            $user->given_name = $data['given_name'];
            $user->surname = $data['surname'];
        }

        if ($request->hasFile('profile_picture')) {
            $this->replaceMedia($user, 'profile_picture', $request->file('profile_picture'));
        }
        if ($request->hasFile('cover_photo')) {
            $this->replaceMedia($user, 'cover_photo', $request->file('cover_photo'));
        }

        $user->save();

        return redirect()->route('profile.edit')->with('status', 'Profile updated.');
    }

    public function updatePassword(): RedirectResponse
    {
        $data = request()->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = Auth::user();
        $user->password = $data['password']; // 'hashed' cast on the model hashes it
        $user->save();

        return redirect()->route('profile.edit')->with('status', 'Password changed.');
    }

    private function replaceMedia($user, string $column, $file): void
    {
        // delete the old object if it lived on our disk.
        if ($user->$column && Storage::disk(self::DISK)->exists($user->$column)) {
            Storage::disk(self::DISK)->delete($user->$column);
        }
        $user->$column = $file->store('profile-media/'.$user->id, self::DISK);
    }
}
