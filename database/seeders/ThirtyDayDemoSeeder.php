<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Favorite;
use App\Models\InstantRequest;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PortfolioImage;
use App\Models\PortfolioProject;
use App\Models\RecurringAvailability;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorService;
use App\Models\WalletTransaction;
use App\Services\VendorSettlement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ThirtyDayDemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'ServiceHub@2026';

    private array $files = [];

    public function run(): void
    {
        $start = now(Booking::SCHEDULE_TIMEZONE);
        $batch = Str::lower(Str::random(10));
        $passwordHash = Hash::make(self::DEMO_PASSWORD);
        $this->files = [];

        try {
            DB::transaction(function () use ($start, $batch, $passwordHash): void {
                $admin = User::factory()->admin()->create(['email' => "admin.$batch@demo.servicehub.example", 'password' => $passwordHash]);
                $offers = $this->catalog($start, $batch, $passwordHash);
                $scheduled = ['pending', 'approved', 'awaiting_payment', 'paid', 'in_progress', 'completed', 'cancelled', 'refunded', 'objection_pending', 'confirmed', 'rejected', 'expired', 'closed'];
                $instant = ['searching', 'cancelled', 'expired', 'confirmed', 'on_the_way', 'arrived', 'in_progress', 'awaiting_payment', 'paid', 'completed', 'cancelled_booking', 'objection_pending'];
                $notificationTypes = ['booking', 'payment', 'refund', 'review', 'vendor_application', 'system', 'wallet', 'service'];

                foreach ($offers as $vendorIndex => $entries) {
                    foreach (range(0, 99) as $index) {
                        $day = 1 + intdiv($index * 29, 100);
                        $hour = [8, 11, 14, 20][$index % 4];
                        $visit = $start->copy()->startOfDay()->addDays($day)->setTime($hour, 0);
                        $date = $index === 0 ? $start->copy() : $visit->copy()->subHours(2);
                        $entry = $entries[intdiv($index, 10)];
                        [$offer] = $entry;
                        $number = $vendorIndex * 100 + $index;
                        $customer = User::factory()->create([
                            'email' => "customer.$number.$batch@demo.servicehub.example",
                            'password' => $passwordHash,
                            'location' => 'عمّان',
                            'created_at' => $date,
                            'updated_at' => $date,
                        ]);
                        $status = $index % 6 === 0 ? 'completed' : $scheduled[$index % count($scheduled)];
                        $booking = $this->booking($customer, $entry, $date, $status, scheduledVisit: $visit);
                        $this->scheduledActivity($booking, $entry, $status, $index >= 13, $admin->id);

                        if ($index < 30) {
                            $instantDate = $index === 0 ? $start->copy() : $start->copy()->startOfDay()->addDays($index)->setTime(17, 0);
                            $this->instantActivity($customer, $entries[0], $instantDate, $instant[$index % count($instant)], $index);
                        }

                        Favorite::factory()->create(['customer_id' => $customer->id, 'vendor_service_id' => $offer->id, 'created_at' => $date]);
                        foreach ([$customer->id, $offer->vendor_id] as $recipient) {
                            Notification::factory()->create([
                                'user_id' => $recipient,
                                'title' => 'تحديث على الحجز #'.$booking->id,
                                'message' => 'يمكنك متابعة تفاصيل الحجز من صفحة حجوزاتك.',
                                'type' => $notificationTypes[$index % count($notificationTypes)],
                                'is_read' => $index % 2 === 0,
                                'created_at' => $date,
                            ]);
                        }
                        if ($index < 10) {
                            ContactMessage::factory()->create([
                                'sender_id' => $offer->vendor_id,
                                'subject' => fake()->randomElement(['استفسار عن تعديل الموعد', 'مراجعة مبلغ الحجز', 'اقتراح خدمة جديدة']),
                                'message' => 'حاب أستفسر عن تفاصيل الخدمة والحجز.',
                                'status' => $index % 2 ? 'pending' : 'reviewed',
                                'reviewed_at' => $index % 2 ? null : $date,
                                'created_at' => $date,
                            ]);
                        }
                    }
                }
            });
        } catch (Throwable $error) {
            foreach ($this->files as [$disk, $path]) {
                Storage::disk($disk)->delete($path);
            }
            throw $error;
        }

        $this->command?->info("حساب التجربة: admin.$batch@demo.servicehub.example");
        $this->command?->info("حساب العميل: customer.0.$batch@demo.servicehub.example");
        $this->command?->info("حساب الفني: vendor.plumbing.$batch@demo.servicehub.example");
        $this->command?->info('كلمة مرور الحسابات التجريبية: '.self::DEMO_PASSWORD);
    }

    private function catalog(Carbon $date, string $batch, string $passwordHash): array
    {
        $offers = [];
        foreach (PresentationSeeder::CATEGORIES as $name => [$slug, $portrait, $title, $description, $price, $minutes]) {
            $category = Category::firstOrCreate(['name' => $name, 'is_deleted' => false], [
                'description' => $description,
                'image' => $this->publicImage("categories/$slug.jpg"),
                'created_at' => $date,
            ]);
            $image = $this->publicImage("services/$slug-1.jpg");
            $service = Service::factory()->create([
                'category_id' => $category->id,
                'name' => $title,
                'description' => $description,
                'image' => $image,
                'created_at' => $date,
            ]);
            $vendor = User::factory()->vendor()->create([
                'email' => "vendor.$slug.$batch@demo.servicehub.example",
                'password' => $passwordHash,
                'location' => 'عمّان',
                'gender' => str_starts_with($portrait, 'f') ? 'female' : 'male',
                'profile_image' => $this->publicImage("vendors/$portrait.jpg"),
                'created_at' => $date,
            ]);
            VendorProfile::factory()->create([
                'user_id' => $vendor->id, 'work_type' => $name, 'applied_at' => $date,
                'instant_available' => true, 'latitude' => 31.95, 'longitude' => 35.91,
                'location_updated_at' => $date, 'last_seen_at' => $date, 'created_at' => $date,
            ]);
            $windows = [];
            foreach (range(0, 6) as $weekday) {
                foreach ([['08:00:00', '16:30:00'], ['17:00:00', '23:59:00']] as [$from, $to]) {
                    $windows[$weekday][] = RecurringAvailability::factory()->create([
                        'vendor_id' => $vendor->id, 'day_of_week' => $weekday,
                        'start_time' => $from, 'end_time' => $to, 'created_at' => $date,
                    ])->id;
                }
            }
            $entries = [];
            $variants = ['زيارة تشخيص', 'معاينة قبل التركيب', 'صيانة دورية', 'زيارة متابعة', 'إصلاح جزئي', 'فحص بعد الإصلاح', 'زيارة تجهيز', 'استبدال القطع', 'فحص شامل'];
            foreach ([null, ...$variants] as $index => $variant) {
                $serviceForOffer = $service;
                if ($variant !== null) {
                    $serviceForOffer = Service::factory()->create([
                        'category_id' => $category->id, 'name' => "$variant — $title", 'description' => $description,
                        'image' => $image, 'created_at' => $date,
                    ]);
                }
                $offer = VendorService::factory()->create([
                    'vendor_id' => $vendor->id, 'service_id' => $serviceForOffer->id, 'description' => $description,
                    'price' => $price + fake()->numberBetween(0, 15), 'duration_minutes' => $minutes,
                    'instant_enabled' => $variant === null, 'image' => $image, 'created_at' => $date,
                ]);
                $projectDate = $date->copy()->addDays($index);
                $project = PortfolioProject::factory()->create([
                    'vendor_id' => $vendor->id, 'name' => $serviceForOffer->name, 'description' => $description,
                    'completion_date' => $projectDate->toDateString(), 'created_at' => $projectDate,
                ]);
                foreach (range(1, 2) as $photoIndex) {
                    $path = "demo/seed/$batch/$slug-$index-$photoIndex.jpg";
                    $this->copyImage("services/$slug-$photoIndex.jpg", 'public', $path);
                    PortfolioImage::factory()->create(['project_id' => $project->id, 'path' => $path, 'created_at' => $projectDate]);
                }
                $entries[] = [$offer, $windows, "services/$slug-2.jpg"];
            }
            $offers[] = $entries;
        }

        foreach (['pending', 'rejected'] as $status) {
            $applicant = User::factory()->create(['password' => $passwordHash, 'created_at' => $date]);
            VendorProfile::factory()->pending()->create(['user_id' => $applicant->id, 'application_status' => $status, 'applied_at' => $date, 'created_at' => $date]);
        }
        foreach (['inactive', 'suspended'] as $status) {
            User::factory()->create(['status' => $status, 'password' => $passwordHash, 'created_at' => $date]);
        }
        [$sample] = $offers[0][0];
        foreach (['pending', 'rejected', 'withdrawn'] as $status) {
            $proposal = Service::factory()->create([
                'category_id' => $sample->service->category_id, 'suggested_by' => $sample->vendor_id,
                'name' => fake()->randomElement(['فحص ضغط المياه', 'تركيب فلتر للمطبخ', 'صيانة محبس الحمام']).' · '.$status,
                'image' => $sample->service->image, 'approval_status' => $status, 'created_at' => $date,
            ]);
            VendorService::factory()->create([
                'vendor_id' => $sample->vendor_id, 'service_id' => $proposal->id,
                'image' => $sample->service->image, 'status' => $status === 'withdrawn' ? 'inactive' : $status,
                'created_at' => $date,
            ]);
        }

        return $offers;
    }

    private function booking(User $customer, array $entry, Carbon $date, string $status, array $extra = [], ?Carbon $scheduledVisit = null): Booking
    {
        [$offer, $windows] = $entry;
        $instant = isset($extra['instant_offer_id']);
        $visit = $scheduledVisit ?? $date->copy()->addMinutes(15);
        $finished = in_array($status, ['completed', 'objection_pending', 'refunded', 'closed'], true);
        $approved = ! in_array($status, ['pending', 'rejected', 'expired'], true);
        $end = $visit->copy()->addMinutes($offer->duration_minutes);
        $created = $date;

        return Booking::factory()->create($extra + [
            'customer_id' => $customer->id, 'vendor_service_id' => $offer->id,
            'availability_id' => $instant ? null : $windows[$visit->dayOfWeek][(int) ($visit->hour >= 17)],
            'booking_date' => $visit->toDateString(), 'start_time' => $visit->format('H:i:s'), 'end_time' => $end->format('H:i:s'),
            'status' => $status, 'created_at' => $created, 'updated_at' => $finished ? $end : $created,
            'approved_at' => $approved ? $created : null,
            'payment_deadline' => $approved ? $created->copy()->addMinutes(30) : null,
            'completed_at' => $finished ? $end : null,
            'vendor_completed_at' => $finished ? $end : null,
            'customer_completed_at' => $finished ? $end : null,
            'objection_deadline' => $finished ? $end->copy()->addHours(24) : null,
            'cancelled_at' => $status === 'cancelled' ? $created->copy()->addMinutes(5) : null,
            'cancelled_by' => $status === 'cancelled' ? $customer->id : null,
        ]);
    }

    private function scheduledActivity(Booking $booking, array $entry, string $status, bool $partialRefund, int $adminId): void
    {
        [$offer, , $source] = $entry;
        $paid = in_array($status, ['paid', 'in_progress', 'completed', 'objection_pending', 'confirmed', 'refunded', 'closed'], true);
        $refund = $status === 'refunded'
            ? round((float) $booking->original_price * ($partialRefund ? 0.5 : 1), 3)
            : null;
        if ($paid || in_array($status, ['awaiting_payment', 'expired'], true)) {
            Payment::factory()->create([
                'booking_id' => $booking->id,
                'payment_method' => fake()->randomElement(['simulated', 'card', 'cash']),
                'status' => match ($status) {
                    'refunded' => $partialRefund ? 'partially_refunded' : 'refunded',
                    'awaiting_payment' => 'pending',
                    'expired' => 'failed',
                    default => 'paid',
                },
                'paid_at' => $paid ? $booking->created_at->copy()->addMinutes(5) : null,
                'paid_amount' => $paid ? $booking->original_price : 0,
                'refunded_amount' => $refund,
                'refunded_at' => $status === 'refunded' ? $booking->completed_at->copy()->addMinute() : null,
                'created_at' => $booking->created_at,
            ]);
        }
        if ($status === 'completed') {
            $review = Review::factory()->create(['booking_id' => $booking->id, 'comment' => 'الفني شرح سبب المشكلة وأنهى الشغل.', 'created_at' => $booking->completed_at]);
            Review::factory()->fromVendor()->create(['booking_id' => $booking->id, 'created_at' => $booking->completed_at]);
            $this->evidence('review', 'review_id', $review->id, $booking->customer_id, $source, $booking->completed_at);
            $this->evidence('completion', 'booking_id', $booking->id, $offer->vendor_id, $source, $booking->completed_at);
            WalletTransaction::factory()->create(['booking_id' => $booking->id]);
        }
        if (in_array($status, ['refunded', 'objection_pending', 'closed'], true)) {
            $caseStatus = match ($status) {
                'refunded' => 'accepted',
                'closed' => 'rejected',
                default => 'pending',
            };
            $this->bookingCase($booking, 'objection', $caseStatus, $source, $booking->completed_at, $refund);
            if ($caseStatus !== 'pending') {
                DB::table('booking_events')->insert([
                    'booking_id' => $booking->id, 'performed_by' => $adminId, 'event_type' => 'admin_decision',
                    'previous_status' => 'pending', 'new_status' => $caseStatus,
                    'created_at' => $booking->completed_at->copy()->addMinute(),
                ]);
            }
        }
    }

    private function instantActivity(User $customer, array $entry, Carbon $date, string $scenario, int $day): void
    {
        [$offer, , $source] = $entry;
        $assigned = ! in_array($scenario, ['searching', 'cancelled', 'expired'], true);
        $request = InstantRequest::create([
            'customer_id' => $customer->id, 'service_id' => $offer->service_id,
            'description' => 'مطلوب فحص ميداني لخدمة '.$offer->service->name,
            'address' => 'عمّان، عنوان تجريبي',
            'latitude' => fake()->randomFloat(6, 31.94, 31.96), 'longitude' => fake()->randomFloat(6, 35.90, 35.92),
            'status' => $assigned ? 'assigned' : $scenario, 'created_at' => $date, 'updated_at' => $date,
        ]);
        $this->evidence('problem', 'instant_request_id', $request->id, $customer->id, $source, $date);
        if ($day === 0) {
            return;
        }

        $instantOffer = $request->offers()->create([
            'vendor_service_id' => $offer->id, 'offered_price' => $offer->price, 'arrival_minutes' => 15,
            'duration_minutes' => $offer->duration_minutes,
            'status' => $assigned ? 'accepted' : match ($scenario) {
                'cancelled' => $day < 12 ? 'withdrawn' : 'invalidated',
                'expired' => 'expired',
                default => 'pending',
            },
            'created_at' => $date, 'updated_at' => $date,
        ]);
        if (! $assigned) {
            return;
        }

        $status = $scenario === 'cancelled_booking' ? 'cancelled' : $scenario;
        $departed = in_array($status, ['on_the_way', 'arrived', 'in_progress', 'awaiting_payment', 'paid', 'completed', 'objection_pending'], true);
        $arrived = $departed && $status !== 'on_the_way';
        $started = $arrived && $status !== 'arrived';
        $arrival = $date->copy()->addMinutes(15);
        $completion = $arrival->copy()->addMinutes($offer->duration_minutes);
        $booking = $this->booking($customer, $entry, $date, $status, [
            'booking_type' => 'instant', 'instant_offer_id' => $instantOffer->id, 'agreed_price' => $offer->price,
            'expected_arrival_at' => $arrival,
            'departed_at' => $departed ? $date->copy()->addMinutes(3) : null,
            'arrived_at' => $arrived ? $arrival : null,
            'arrival_verified_at' => $started ? $arrival : null,
            'started_at' => $started ? $arrival : null,
            'payment_deadline' => in_array($status, ['awaiting_payment', 'paid', 'completed', 'objection_pending'], true) ? $completion->copy()->addHours(24) : null,
            'completed_at' => in_array($status, ['awaiting_payment', 'paid', 'completed', 'objection_pending'], true) ? $completion : null,
            'settlement_status' => $status === 'objection_pending' ? 'on_hold' : 'pending',
        ]);
        if ($arrived) {
            DB::table('booking_events')->insert([
                'booking_id' => $booking->id, 'performed_by' => $offer->vendor_id,
                'event_type' => 'arrival_recorded', 'latitude' => $request->latitude, 'longitude' => $request->longitude,
                'created_at' => $arrival,
            ]);
        }
        if ($started) {
            DB::table('booking_events')->insert([
                'booking_id' => $booking->id, 'performed_by' => $offer->vendor_id,
                'event_type' => 'arrival_verified', 'created_at' => $arrival,
            ]);
        }
        if (in_array($status, ['in_progress', 'awaiting_payment', 'paid', 'completed'], true)) {
            DB::table('booking_events')->insert([
                'booking_id' => $booking->id, 'performed_by' => $offer->vendor_id,
                'event_type' => 'price_adjustment', 'previous_price' => $offer->price,
                'proposed_price' => $offer->price + 5,
                'adjustment_status' => match ($status) {
                    'in_progress' => 'pending',
                    'awaiting_payment' => 'withdrawn',
                    default => 'rejected',
                },
                'created_at' => $arrival->copy()->addMinutes(5),
                'resolved_at' => $status === 'in_progress' ? null : $arrival->copy()->addMinutes(10),
            ]);
        }
        DB::table('booking_events')->insert([
            'booking_id' => $booking->id, 'performed_by' => $offer->vendor_id,
            'event_type' => $status === 'cancelled' ? 'cancellation' : 'status_change',
            'status_type' => 'operational', 'new_status' => $status, 'created_at' => $date,
        ]);
        if (in_array($status, ['paid', 'completed', 'objection_pending', 'awaiting_payment'], true)) {
            Payment::factory()->create([
                'booking_id' => $booking->id, 'payment_method' => 'simulated',
                'status' => $status === 'awaiting_payment' ? 'pending' : 'paid',
                'paid_amount' => $status === 'awaiting_payment' ? 0 : $booking->agreed_price,
                'paid_at' => $status === 'awaiting_payment' ? null : $date->copy()->addHours(3),
                'created_at' => $date->copy()->addHours(3),
            ]);
        }
        if ($status === 'completed') {
            $this->evidence('completion', 'booking_id', $booking->id, $offer->vendor_id, $source, $booking->completed_at, instant: true);
            $settledAt = $booking->objection_deadline;
            $share = VendorSettlement::vendorShare((string) $booking->agreed_price);
            $commission = number_format((float) $booking->agreed_price - (float) $share, 3, '.', '');
            $booking->forceFill(['settlement_status' => 'settled', 'settled_at' => $settledAt, 'updated_at' => $settledAt])->save();
            foreach ([
                ['payout', 'vendor', $offer->vendor_id, $share],
                ['commission', 'platform', null, $commission],
            ] as [$type, $account, $owner, $amount]) {
                WalletTransaction::factory()->create([
                    'booking_id' => $booking->id, 'user_id' => $owner, 'account_type' => $account,
                    'transaction_type' => $type, 'status' => 'posted', 'direction' => 'credit',
                    'amount' => $amount, 'description' => 'تسوية تجريبية للحجز الفوري', 'created_at' => $settledAt,
                ]);
            }
            DB::table('booking_events')->insert([
                'booking_id' => $booking->id, 'event_type' => 'settlement_change', 'status_type' => 'settlement',
                'previous_status' => 'pending', 'new_status' => 'settled', 'created_at' => $settledAt,
            ]);
        }
        $caseType = match ($status) {
            'on_the_way' => 'vendor_late',
            'arrived' => $day < 12 ? 'arrival_verification_issue' : 'customer_no_show',
            'in_progress' => $day < 12 ? 'stuck_booking' : 'suspected_fraud',
            'awaiting_payment' => $day < 12 ? 'non_execution' : 'partial_service',
            'cancelled' => 'exceptional_cancellation',
            'objection_pending' => $day < 12 ? 'objection' : 'completion_issue',
            default => null,
        };
        if ($caseType) {
            $this->bookingCase($booking, $caseType, 'pending', $source, $booking->completed_at ?? $date->copy()->addMinutes(30));
        }
    }

    private function bookingCase(Booking $booking, string $type, string $status, string $source, Carbon $date, ?float $refund = null): void
    {
        $case = RefundRequest::factory()->create([
            'booking_id' => $booking->id, 'submitted_by' => $booking->customer_id,
            'case_type' => $type, 'status' => $status, 'reason' => 'بلاغ تجريبي لمراجعة نتيجة الزيارة.',
            'refund_amount' => $refund,
            'reviewed_at' => $status === 'pending' ? null : $date->copy()->addMinute(), 'created_at' => $date,
        ]);
        $this->evidence($type === 'objection' ? 'objection' : 'exceptional_case', 'refund_request_id', $case->id, $booking->customer_id, $source, $date, instant: $booking->booking_type === 'instant');
    }

    private function evidence(string $kind, string $parent, int $id, int $uploader, string $source, Carbon $date, bool $instant = false): void
    {
        $directory = $instant || $kind === 'problem' ? 'instant-evidence' : 'booking-evidence';
        $path = "$directory/".Str::uuid().'.jpg';
        $this->copyImage($source, 'local', $path);
        DB::table('images')->insert([
            'kind' => $kind, $parent => $id, 'uploaded_by' => $uploader, 'path' => $path, 'created_at' => $date,
        ]);
    }

    private function publicImage(string $source): string
    {
        $path = "demo/$source";
        $this->copyImage($source, 'public', $path);

        return $path;
    }

    private function copyImage(string $source, string $disk, string $path): void
    {
        $storage = Storage::disk($disk);
        if ($storage->exists($path)) {
            return;
        }
        $bytes = file_get_contents(database_path("demo-images/$source"));
        if ($bytes === false || ! $storage->put($path, $bytes)) {
            throw new RuntimeException("Unable to copy demo image $source");
        }
        $this->files[] = [$disk, $path];
    }
}
