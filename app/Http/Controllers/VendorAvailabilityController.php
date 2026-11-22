<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\RecurringAvailability;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorAvailabilityController extends Controller
{
    private const DAYS = [6 => 'السبت', 0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة'];

    public function index(Request $request)
    {
        $availabilities = $request->user()->recurringAvailabilities()
            ->withCount('bookings')
            ->orderByRaw('CASE WHEN day_of_week = 6 THEN -1 ELSE day_of_week END')
            ->orderBy('start_time')
            ->get();

        return view('pages.vendor.vendor-availability', ['availabilities' => $availabilities, 'days' => self::DAYS]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedWindow($request);
        $vendorId = (int) $request->user()->id;
        $start = $data['start_time'].':00';
        $end = $data['end_time'].':00';

        DB::transaction(function () use ($vendorId, $data, $start, $end) {
            User::query()->whereKey($vendorId)->lockForUpdate()->firstOrFail();
            $this->assertNoOverlap($vendorId, (int) $data['day_of_week'], $start, $end);
            RecurringAvailability::create([
                'vendor_id' => $vendorId,
                'day_of_week' => (string) $data['day_of_week'],
                'start_time' => $start,
                'end_time' => $end,
                'is_active' => true,
            ]);
        });

        return redirect()->route('vendor.availability.index')->with('success', 'تمت إضافة الفترة بنجاح.');
    }

    public function update(Request $request, RecurringAvailability $availability)
    {
        $vendorId = (int) $request->user()->id;
        abort_unless((int) $availability->vendor_id === $vendorId, 404);
        $data = $this->validatedWindow($request, true);
        $start = $data['start_time'].':00';
        $end = $data['end_time'].':00';
        $isActive = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : (bool) $availability->is_active;

        DB::transaction(function () use ($vendorId, $availability, $data, $start, $end, $isActive) {
            User::query()->whereKey($vendorId)->lockForUpdate()->firstOrFail();
            $current = RecurringAvailability::query()
                ->whereKey($availability->id)
                ->where('vendor_id', $vendorId)
                ->lockForUpdate()
                ->firstOrFail();
            $scheduleChanged = (int) $current->day_of_week !== (int) $data['day_of_week']
                || substr($current->start_time, 0, 5) !== $data['start_time']
                || substr($current->end_time, 0, 5) !== $data['end_time'];

            if (($scheduleChanged || ! $isActive) && $this->hasUpcomingBookings($current)) {
                throw ValidationException::withMessages(['availability' => 'لا يمكن تعديل أو تعطيل فترة مرتبطة بحجوزات قادمة.']);
            }
            if ($isActive) {
                $this->assertNoOverlap($vendorId, (int) $data['day_of_week'], $start, $end, $current->id);
            }
            $current->update([
                'day_of_week' => (string) $data['day_of_week'],
                'start_time' => $start,
                'end_time' => $end,
                'is_active' => $isActive,
            ]);
        });

        return redirect()->route('vendor.availability.index')->with('success', 'تم تحديث الفترة بنجاح.');
    }

    public function destroy(Request $request, RecurringAvailability $availability)
    {
        abort_unless((int) $availability->vendor_id === (int) $request->user()->id, 404);
        if ($this->hasUpcomingBookings($availability)) {
            throw ValidationException::withMessages(['availability' => 'لا يمكن تعطيل فترة مرتبطة بحجوزات قادمة.']);
        }
        $availability->update(['is_active' => false]);

        return redirect()->route('vendor.availability.index')->with('success', 'تم تعطيل الفترة مع الحفاظ على سجل الحجوزات.');
    }

    private function validatedWindow(Request $request, bool $updating = false): array
    {
        $rules = [
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ];
        if ($updating) {
            $rules['is_active'] = ['sometimes', 'boolean'];
        }
        $data = $request->validate($rules);
        if ($data['end_time'] <= $data['start_time']) {
            throw ValidationException::withMessages(['end_time' => 'يجب أن يكون وقت النهاية بعد وقت البداية في اليوم نفسه.']);
        }

        return $data;
    }

    private function assertNoOverlap(int $vendorId, int $day, string $start, string $end, ?int $exceptId = null): void
    {
        $overlaps = RecurringAvailability::query()
            ->where('vendor_id', $vendorId)
            ->where('day_of_week', (string) $day)
            ->where('is_active', true)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($exceptId, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages(['start_time' => 'هذه الفترة تتداخل مع فترة فعّالة أخرى في اليوم نفسه.']);
        }
    }

    private function hasUpcomingBookings(RecurringAvailability $availability): bool
    {
        return $availability->bookings()
            ->whereDate('booking_date', '>=', today(Booking::SCHEDULE_TIMEZONE))
            ->blockingSlot()
            ->exists();
    }
}
