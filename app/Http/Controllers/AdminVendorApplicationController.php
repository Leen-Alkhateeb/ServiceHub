<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\VendorProfile;
use Illuminate\Http\Request;

class AdminVendorApplicationController extends Controller
{
    public function index(){
        $applications = VendorProfile::paginate(5);
        return view('pages.admin.admin-applications', compact('applications'));
    }
    
     public function approved($id){
        $application = VendorProfile::with('user')->findOrFail($id);
        $application->application_status='approved';
        $application->user->role='vendor';
        $application->save();
        $application->user->notifications()->create([
        'title' => 'تمت الموافقة على طلب الانضمام',
        'message' => 'تهانينا! تمت الموافقة على طلب انضمامك كفني في ServiceHub.',
        'type' => 'vendor_application',
        ]);
        
    return redirect()
        ->route('admin.admin-applications')
        ->with('success', 'تمت الموافقة على طلب انضمام الفني بنجاح.');
    }

    public function rejected($id){
        $application = VendorProfile::with('user')->findOrFail($id);
        $application->application_status='rejected';
        $application->save();
        $application->user->notifications()->create([
        'title' => 'لم الموافقة على طلب الانضمام',
        'message' => 'نعتذر! لم تمت الموافقة على طلب انضمامك كفني في ServiceHub.',
        'type' => 'vendor_application',
        ]);

    return redirect()
        ->route('admin.admin-applications')
        ->with('success', 'تم رفض طلب انضمام الفني .');
    }
}
