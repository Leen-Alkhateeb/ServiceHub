<?php

namespace App\Http\Controllers;

use App\Services\AdminPeriod;

use App\Models\VendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminVendorApplicationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,approved,rejected,reviewed'],
            'work_type' => ['nullable', 'string', 'max:255'],
            'experience_from' => ['nullable', 'integer', 'min:0'],
            'experience_to' => ['nullable', 'integer', 'min:0', ...($request->filled('experience_from') ? ['gte:experience_from'] : [])],
            'applied_from' => ['nullable', 'date_format:Y-m-d'],
            'applied_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('applied_from') ? ['after_or_equal:applied_from'] : [])],
            'permit_from' => ['nullable', 'date_format:Y-m-d'],
            'permit_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('permit_from') ? ['after_or_equal:permit_from'] : [])],
        ]);

        $query = AdminPeriod::apply(VendorProfile::with('user'), 'applied_at');
        if ($search = $filters['search'] ?? null) {
            $query->where(function ($rows) use ($search) {
                $rows->where('bio', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($users) use ($search) {
                        foreach (preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) as $word) {
                            $users->where(fn ($match) => $match
                                ->where('first_name', 'like', '%'.$word.'%')
                                ->orWhere('last_name', 'like', '%'.$word.'%')
                                ->orWhere('email', 'like', '%'.$word.'%'));
                        }
                    });
            });
        }
        if ($status = $filters['status'] ?? null) {
            $query->whereIn('application_status', $status === 'reviewed' ? ['approved', 'rejected'] : [$status]);
        }
        if ($workType = $filters['work_type'] ?? null) {
            $query->where('work_type', $workType);
        }

        foreach (['applied' => 'applied_at', 'permit' => 'permit_expiry_date', 'experience' => 'experience_years'] as $filter => $column) {
            foreach (['from' => '>=', 'to' => '<='] as $bound => $operator) {
                $value = $filters[$filter.'_'.$bound] ?? null;
                if ($value !== null) {
                    $filter === 'experience'
                        ? $query->where($column, $operator, $value)
                        : $query->whereDate($column, $operator, $value);
                }
            }
        }

        $applications = $query
            ->orderByRaw("CASE WHEN application_status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN application_status = 'pending' THEN COALESCE(applied_at, created_at) ELSE updated_at END DESC")
            ->latest('id')->paginate(5)->withQueryString();
        $workTypes = AdminPeriod::apply(VendorProfile::query(), 'applied_at')->whereNotNull('work_type')->where('work_type', '!=', '')
            ->distinct()->orderBy('work_type')->pluck('work_type');

        return view('pages.admin.admin-applications', compact('applications', 'filters', 'workTypes'));
    }

    public function approved(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            $application = VendorProfile::with('user')->where('application_status', 'pending')->lockForUpdate()->findOrFail($id);
            $application->update(['application_status' => 'approved']);
            $application->user->update(['role' => 'vendor']);
            $application->user->notifications()->create([
                'title' => 'تمت الموافقة على طلب الانضمام',
                'message' => 'تهانينا! تمت الموافقة على طلب انضمامك كفني في '.config('app.site_name').'.',
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
                'message' => 'نعتذر، لم تتم الموافقة على طلب انضمامك كفني في '.config('app.site_name').'.',
                'type' => 'vendor_application',
            ]);
        });

        return redirect()
            ->route('admin.admin-applications')
            ->with('success', 'تم رفض طلب انضمام الفني.')
            ->with('decision_rejected', true);
    }

    public function permit(VendorProfile $application)
    {
        $path = $application->work_permit_path;
        abort_unless($path && str_starts_with($path, 'vendor-permits/') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
