<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminMessageController extends Controller
{
    private const STATUSES = [
        'pending' => 'بانتظار المراجعة',
        'reviewed' => 'تمت المراجعة',
    ];

    private const ROLES = [
        'customer' => 'عميل',
        'vendor' => 'مزوّد',
        'admin' => 'أدمن',
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
        ]);

        $search = trim($filters['q'] ?? '');

        $query = ContactMessage::with('sender');

        if ($search !== '') {
            $query->where(function ($messages) use ($search) {
                $messages->where('subject', 'like', '%' . $search . '%')
                    ->orWhereHas('sender', function ($sender) use ($search) {
                        $sender->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $messages = $query->latest('id')->paginate(15)->withQueryString();

        return view('pages.admin.admin-messages', [
            'messages' => $messages,
            'filters' => $filters,
            'statuses' => self::STATUSES,
            'roles' => self::ROLES,
            'totalCount' => ContactMessage::count(),
            'pendingCount' => ContactMessage::where('status', 'pending')->count(),
            'reviewedCount' => ContactMessage::where('status', 'reviewed')->count(),
        ]);
    }

    public function show(ContactMessage $message)
    {
        $message->load('sender');

        return view('pages.admin.admin-messages-show', [
            'message' => $message,
            'statusLabel' => self::STATUSES[$message->status] ?? $message->status,
            'senderRole' => self::ROLES[$message->sender?->role] ?? $message->sender?->role,
        ]);
    }

    public function update(ContactMessage $message)
    {
        if ($message->status === 'reviewed') {
            return redirect()
                ->route('admin.messages.show', $message)
                ->with('error', 'هذه الرسالة تمت مراجعتها مسبقاً.');
        }

        $message->update([
            'status' => 'reviewed',
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.messages.show', $message)
            ->with('success', 'تم تعليم الرسالة كمراجَعة.');
    }
}
