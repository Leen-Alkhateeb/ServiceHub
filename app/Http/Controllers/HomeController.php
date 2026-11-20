<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = $this->visibleCategories()->limit(5)->get();
        $vendors = $this->visibleVendors()->limit(3)->get();

        return view('pages.public.index', compact('categories', 'vendors'));
    }

    public function categories(): View
    {
        return view('pages.public.directory', [
            'title' => 'أقسام الصيانة',
            'categories' => $this->visibleCategories()->paginate(12),
        ]);
    }

    public function category(Category $category): View
    {
        abort_if($category->is_deleted, 404);
        $vendors = $this->visibleVendors()
            ->whereExists(fn (Builder $offers) => $offers->selectRaw('1')
                ->from('vendor_services')
                ->join('services', 'services.id', '=', 'vendor_services.service_id')
                ->whereColumn('vendor_services.vendor_id', 'users.id')
                ->where('vendor_services.status', 'active')
                ->where('services.category_id', $category->id)
                ->where('services.is_deleted', false)
                ->where('services.approval_status', 'approved'))
            ->paginate(12);

        return view('pages.public.category', compact('category', 'vendors'));
    }

    public function vendors(): View
    {
        return view('pages.public.directory', [
            'title' => 'الفنيون',
            'vendors' => $this->visibleVendors()->paginate(12),
        ]);
    }

    private function visibleCategories(): Builder
    {
        return DB::table('categories')->where('is_deleted', false)->latest('id');
    }

    private function visibleVendors(): Builder
    {
        return DB::table('vendor_profiles as profiles')
            ->join('users', 'users.id', '=', 'profiles.user_id')
            ->where('profiles.application_status', 'approved')
            ->where('users.role', 'vendor')
            ->where('users.status', 'active')
            ->orderByDesc('profiles.id')
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.location', 'users.profile_image', 'profiles.work_type');
    }
}
