<?php

namespace App\Http\Controllers;

use App\Models\VendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminVendorApplicationController extends Controller
{
    public function index()
    {
        $applications = VendorProfile::paginate(5);

        return view('pages.admin.admin-applications', compact('applications'));
    }

    public function approved(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            $application = VendorProfile::with('user')->where('application_status', 'pending')->lockForUpdate()->findOrFail($id);
            $application->update(['application_status' => 'approved']);
            $application->user->update(['role' => 'vendor']);
            $application->user->notifications()->create([
                'title' => 'تمت الموافقة على طلب الانضمام',
                'message' => 'تهانينا! تمت الموافقة على طلب انضمامك كفني في ServiceHub.',
                'type' => 'vendor_application',
            ]);
        });

        return redirect()
            ->route('admin.admin-applications')
            ->with('success', 'تمت الموافقة على طلب انضمام الفني بنجاح.');
    }

    public function rejected(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            $application = VendorProfile::with('user')->where('application_status', 'pending')->lockForUpdate()->findOrFail($id);
            $application->update(['application_status' => 'rejected']);
            $application->user->notifications()->create([
                'title' => 'لم تتم الموافقة على طلب الانضمام',
                'message' => 'نعتذر، لم تتم الموافقة على طلب انضمامك كفني في ServiceHub.',
                'type' => 'vendor_application',
            ]);
        });

        return redirect()
            ->route('admin.admin-applications')
            ->with('success', 'تم رفض طلب انضمام الفني.');
    }

    public function permit(VendorProfile $application)
    {
        $path = $application->work_permit_path;
        abort_unless($path && str_starts_with($path, 'vendor-permits/') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
