<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AccountPictureController extends Controller
{
    public function show(Request $request, User $account): StreamedResponse
    {
        $isOwner = (int) $request->user()?->id === (int) $account->id;
        abort_unless($isOwner || ($account->role === 'vendor' && $account->status === 'active'
            && $account->vendorProfile?->application_status === 'approved'), 404);

        $file = AccountPhoto::storedFile($account->id, $account->profile_image);
        abort_unless($file, 404);
        [$diskName, $path] = $file;
        $disk = Storage::disk($diskName);
        $type = $disk->mimeType($path);
        abort_unless(in_array($type, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'], true), 404);

        return $disk->response($path, null, ['Content-Type' => $type, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('picture', [
            'profile_image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120', 'dimensions:max_width=5000,max_height=5000'],
        ]);
        $user = $request->user();
        $path = $data['profile_image']->store('profile-photos/'.$user->id, 'local');
        if (! $path) {
            throw ValidationException::withMessages(['profile_image' => 'تعذر حفظ الصورة. حاول مرة أخرى.'])->errorBag('picture');
        }

        try {
            $previous = DB::transaction(function () use ($user, $path) {
                $account = User::query()->lockForUpdate()->findOrFail($user->id);
                $previous = $account->profile_image;
                $account->update(['profile_image' => $path]);

                return $previous;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return $this->finishChange($user, $previous, 'تم تحديث الصورة الشخصية.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $previous = DB::transaction(function () use ($user) {
            $account = User::query()->lockForUpdate()->findOrFail($user->id);
            $previous = $account->profile_image;
            $account->update(['profile_image' => null]);

            return $previous;
        });

        return $this->finishChange($user, $previous, 'تم حذف الصورة الشخصية.');
    }

    private function finishChange(User $user, ?string $previous, string $message): RedirectResponse
    {
        if (AccountPhoto::isManaged($user->id, $previous)) {
            Storage::disk('local')->delete($previous);
        }

        $destination = $user->role === 'vendor' && $user->status === 'active' && $user->vendorProfile()->exists()
            ? 'vendor.profile' : 'account.profile';

        return redirect()->route($destination)->with('picture_status', $message);
    }
}
