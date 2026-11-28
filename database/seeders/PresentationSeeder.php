<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Payment;
use App\Models\RecurringAvailability;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;











class PresentationSeeder extends Seeder
{
     
    public const CATEGORIES = [
        'السباكة' => ['plumbing', 'm07', 'تركيب خلاط مطبخ أو حمام', 'تركيب خلاط جديد وفحص الوصلات والتأكد من عدم وجود تسريب.', 25, 60],
        'الكهرباء' => ['electrical', 'm01', 'تركيب مقابس ومفاتيح كهرباء', 'تركيب أو استبدال المقابس والمفاتيح مع فحص سلامة التمديدات.', 20, 60],
        'صيانة المكيفات' => ['cooling', 'm08', 'فحص ضغط غاز المكيف وتعبئته', 'قياس ضغط الغاز وتعبئته عند الحاجة مع فحص أماكن التسريب.', 35, 60],
        'صيانة الأجهزة المنزلية' => ['appliances', 'm03', 'صيانة الثلاجة', 'تشخيص ضعف التبريد وفحص الكمبروسر والترموستات وتبديل القطع التالفة.', 30, 60],
        'النجارة' => ['carpentry', 'm02', 'تركيب وتعديل خزائن المطبخ', 'تركيب الخزائن وضبط الأبواب والمفصلات والأدراج.', 60, 120],
        'الدهان' => ['painting', 'm05', 'دهان غرفة كاملة', 'تجهيز الجدران ومعالجة التشققات ودهان الغرفة وجهين بلون من اختيارك.', 120, 120],
        'الأقفال' => ['locks', 'm09', 'فتح باب مقفل بدون كسر', 'فتح الأبواب المقفلة باحترافية ودون إتلاف القفل أو الباب.', 20, 60],
        'الأرضيات' => ['flooring', 'm10', 'تركيب أرضيات باركيه', 'تركيب باركيه للغرفة مع تسوية الأرضية وتركيب الحواف.', 150, 120],
        'الزجاج والألمنيوم' => ['glass', 'm11', 'تركيب نوافذ ألمنيوم', 'قياس وتركيب نوافذ ألمنيوم مع العزل والسيليكون.', 110, 120],
        'العزل' => ['insulation', 'm12', 'عزل حراري للأسطح والجدران', 'تركيب مواد عزل حراري لتقليل فقدان الحرارة وتوفير الطاقة.', 140, 120],
        'التدفئة' => ['heating', 'm04', 'تنظيف وصيانة الشوفاج', 'تنظيف الرادياتير وتفريغ الهواء وضبط الصمامات قبل الشتاء.', 30, 60],
        'صيانة الحدائق' => ['garden', 'm06', 'تقليم الأشجار وتنسيق الحديقة', 'تقليم الأشجار والأسيجة وتنظيف المساحات الخضراء.', 45, 90],
        'التنظيف المتخصص' => ['cleaning', 'f02', 'تنظيف الكنب والسجاد', 'تنظيف عميق للكنب والسجاد بالبخار ومواد آمنة على الأطفال.', 50, 90],
        'الطاقة الشمسية' => ['solar', 'f03', 'تنظيف الألواح الشمسية', 'غسيل الألواح بمعدات خاصة لرفع كفاءة إنتاج الكهرباء.', 35, 60],
        'الشبكات المنزلية' => ['networks', 'f01', 'تمديد كوابل الشبكة', 'تمديد كوابل إنترنت للغرف وتركيب نقاط شبكة وراوتر إضافي.', 40, 90],
        'الإنارة' => ['lighting', 'f04', 'تركيب إنارة LED مخفية', 'تركيب شرائط LED للأسقف والمطابخ مع المحولات.', 55, 90],
    ];

     
    private const NEW_VENDORS = [
        ['vendor.ibrahim.demo@servicehub.example', 'إبراهيم', 'العتوم', 'male', 'السباكة', 'm14', 22, 'عمّان', 'فني سباكة بخبرة طويلة في تمديدات المنازل وكشف التسريبات.'],
        ['vendor.anas.demo@servicehub.example', 'أنس', 'القضاة', 'male', 'الكهرباء', 'm13', 18, 'إربد', 'كهربائي منازل متخصص في لوحات التوزيع والتمديدات الجديدة.'],
        ['vendor.muath.demo@servicehub.example', 'معاذ', 'الشوابكة', 'male', 'صيانة المكيفات', 'm15', 9, 'الزرقاء', 'فني تكييف وتبريد، صيانة وتركيب جميع أنواع المكيفات.'],
        ['vendor.hiba.demo@servicehub.example', 'هبة', 'الطراونة', 'female', 'التنظيف المتخصص', 'f05', 6, 'عمّان', 'خدمات تنظيف منزلي متخصص بمعدات حديثة ومواد آمنة.'],
    ];

     
    private const CUSTOMERS = [
        ['رامي', 'الحوراني', 'male', 'عمّان'],
        ['ديما', 'المجالي', 'female', 'عمّان'],
        ['طارق', 'البطاينة', 'male', 'إربد'],
        ['لينا', 'الكركي', 'female', 'الزرقاء'],
        ['حمزة', 'العجلوني', 'male', 'عمّان'],
        ['نادين', 'الصمادي', 'female', 'السلط'],
        ['محمد', 'الرواشدة', 'male', 'الكرك'],
        ['ريما', 'الخصاونة', 'female', 'إربد'],
        ['زيد', 'الفايز', 'male', 'مادبا'],
        ['هيا', 'الدباس', 'female', 'عمّان'],
        ['قيس', 'الشرع', 'male', 'العقبة'],
        ['جنى', 'الحياري', 'female', 'عمّان'],
    ];

    private const REVIEWS = [
        5 => ['خدمة ممتازة، الفني وصل بالموعد وشرح لي المشكلة بوضوح.', 'شغل نظيف ومرتب، وأنصح فيه.', 'سرعة بالإنجاز وسعر مناسب جداً.'],
        4 => ['الشغل كان جيد، بس تأخر شوي عن الموعد.', 'خدمة جيدة والتعامل محترم.', 'حل المشكلة، وكان ممكن ينظف المكان أكثر بعد الشغل.'],
        3 => ['الخدمة مقبولة، احتجت أتواصل معه مرة ثانية لإكمال الشغل.', 'النتيجة كويسة بس السعر كان أعلى من المتوقع.'],
    ];

    private const MESSAGES = [
        ['كيف أعدّل موعد الحجز؟', 'حجزت خدمة صيانة مكيف وبدي أغيّر الموعد ليوم ثاني، كيف بقدر أعمل هيك؟'],
        ['استفسار عن العربون', 'هل العربون بينرد إذا الفني ألغى الموعد؟'],
        ['مشكلة بالدفع', 'حاولت أدفع العربون بالبطاقة وطلعتلي رسالة خطأ، شو الحل؟'],
        ['اقتراح خدمة جديدة', 'بتمنى تضيفوا خدمة صيانة سخانات الماء الشمسية.'],
        ['شكر للفريق', 'تجربتي مع المنصة كانت ممتازة، شكراً على سرعة الرد.'],
        ['تغطية المناطق', 'هل الخدمات متوفرة في العقبة؟'],
        ['فاتورة الحجز', 'بدي نسخة من فاتورة آخر حجز، وين بلاقيها؟'],
        ['التسجيل كفني', 'أنا فني كهرباء، شو الأوراق المطلوبة عشان أنضم للمنصة؟'],
    ];

    private const WORK_DAYS = [0, 1, 2, 3, 4, 6];  

    private array $taken = [];

    public function run(): void
    {
        $this->copyImages();

        DB::transaction(function () {
            $offers = $this->seedCatalog();
            $customers = $this->seedCustomers();
            $this->seedBookings($customers, $offers);
            $this->seedMessages($customers);
        });
    }

     
    private function copyImages(): void
    {
        $disk = Storage::disk('public');

        foreach (['categories', 'services', 'vendors'] as $folder) {
            foreach (glob(database_path("demo-images/$folder/*.jpg")) as $file) {
                $target = "demo/$folder/".basename($file);

                if (! $disk->exists($target)) {
                    $disk->put($target, file_get_contents($file));
                }
            }
        }
    }

     
    private function seedCatalog(): array
    {
        $offers = [];

        foreach (self::CATEGORIES as $name => [$key, $photo, $newName, $newDescription, $newPrice, $minutes]) {
            $category = Category::where('name', $name)->where('is_deleted', false)->first();

            if (! $category) {
                continue;
            }

            $this->fillImage($category, "demo/categories/$key.jpg");

             
            $mainService = Service::where('category_id', $category->id)
                ->whereNull('suggested_by')
                ->oldest('id')
                ->first();

            if ($mainService) {
                $this->fillImage($mainService, "demo/services/$key-1.jpg");
            }

            $newService = Service::firstOrCreate(
                ['category_id' => $category->id, 'name' => $newName],
                [
                    'description' => $newDescription,
                    'image' => "demo/services/$key-2.jpg",
                    'approval_status' => 'approved',
                    'is_deleted' => false,
                    'is_active' => true,
                ]
            );

             
            $vendor = User::where('email', "$key.seed@servicehub.example")->first();

            if ($vendor) {
                $this->fillPhoto($vendor, $photo);
                $this->addWorkDays($vendor);

                $offers[] = $this->offer($vendor, $newService, $newPrice, $minutes, $newDescription);

                if ($mainOffer = VendorService::where('vendor_id', $vendor->id)->where('service_id', $mainService?->id)->first()) {
                    $offers[] = $mainOffer;
                }
            }
        }

        foreach (self::NEW_VENDORS as [$email, $first, $last, $gender, $categoryName, $photo, $years, $city, $bio]) {
            $category = Category::where('name', $categoryName)->where('is_deleted', false)->first();

            if (! $category) {
                continue;
            }

            $vendor = User::firstOrCreate(['email' => $email], [
                'first_name' => $first,
                'last_name' => $last,
                'password' => Hash::make('password'),
                'phone' => $this->phone($email),
                'location' => $city,
                'gender' => $gender,
                'role' => 'vendor',
                'status' => 'active',
            ]);
            $this->fillPhoto($vendor, $photo);

            VendorProfile::firstOrCreate(['user_id' => $vendor->id], [
                'experience_years' => $years,
                'bio' => $bio,
                'work_type' => $categoryName,
                'work_permit_path' => 'vendor-permits/demo-placeholder.pdf',
                'permit_expiry_date' => now()->addYear()->toDateString(),
                'application_status' => 'approved',
                'applied_at' => now()->subMonths(3),
            ]);

            $this->addWorkDays($vendor);

             
            $services = Service::where('category_id', $category->id)
                ->where('approval_status', 'approved')
                ->where('is_deleted', false)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            foreach ($services as $index => $service) {
                $reference = VendorService::where('service_id', $service->id)->where('status', 'active')->oldest('id')->first();
                $price = $reference ? (float) $reference->price + ($index % 2 === 0 ? 5 : -5) : 40;

                $offers[] = $this->offer($vendor, $service, max(15, $price), $reference?->duration_minutes ?? 60, $service->description);
            }
        }

        return $offers;
    }

    private function seedCustomers(): array
    {
        $customers = [];

        foreach (self::CUSTOMERS as $index => [$first, $last, $gender, $city]) {
            $number = str_pad($index + 1, 2, '0', STR_PAD_LEFT);

            $customers[] = User::firstOrCreate(['email' => "customer.$number.demo@servicehub.example"], [
                'first_name' => $first,
                'last_name' => $last,
                'password' => Hash::make('password'),
                'phone' => $this->phone($first.$number),
                'location' => $city,
                'gender' => $gender,
                'role' => 'customer',
                'status' => 'active',
            ]);
        }

        return $customers;
    }

     
    private function seedBookings(array $customers, array $offers): void
    {
        if (! $offers) {
            return;
        }

        $ratings = [5, 4, 5, 3, 5, 4, 5, 5, 4, 5, 3, 4];

        foreach ($customers as $i => $customer) {
            if (Booking::withTrashed()->where('customer_id', $customer->id)->exists()) {
                continue;
            }

            $plan = [
                ['completed', -(10 + $i * 2)],
                ['completed', -(3 + $i % 4)],
                ['confirmed', 2 + $i % 10],
            ];

            if ($i % 3 === 0) {
                $plan[] = ['cancelled', 4 + $i % 5];
            } elseif ($i % 3 === 1) {
                $plan[] = ['pending', 4 + $i % 6];
            }

            foreach ($plan as $n => [$status, $days]) {
                $offer = $offers[($i * 3 + $n * 5) % count($offers)];
                $booking = $this->book($customer, $offer, $status, $days, $i);

                if (! $booking) {
                    continue;
                }

                if (in_array($status, ['completed', 'confirmed'], true)) {
                    Payment::create([
                        'booking_id' => $booking->id,
                        'amount' => round((float) $booking->original_price * 0.5, 2),
                        'payment_method' => 'card',
                        'status' => 'paid',
                        'paid_at' => $booking->approved_at->copy()->addMinutes(10),
                        'created_at' => $booking->approved_at->copy()->addMinutes(10),
                    ]);
                }

                if ($status === 'completed' && $n === 0) {
                    $rating = $ratings[$i];
                    $comments = self::REVIEWS[$rating];

                    Review::create([
                        'booking_id' => $booking->id,
                        'reviewer_id' => $customer->id,
                        'reviewed_user_id' => $offer->vendor_id,
                        'rating' => $rating,
                        'comment' => $comments[$i % count($comments)],
                        'is_active' => true,
                        'created_at' => $booking->completed_at->copy()->addDay(),
                    ]);
                }

                 
                if ($status === 'completed' && $n === 1 && in_array($i, [1, 4], true)) {
                    RefundRequest::create([
                        'booking_id' => $booking->id,
                        'submitted_by' => $customer->id,
                        'reason' => $i === 1
                            ? 'رجعت المشكلة بعد يومين من الزيارة وبطلب مراجعة الحجز.'
                            : 'الفني ما أكمل الشغل المتفق عليه.',
                        'status' => $i === 1 ? 'pending' : 'rejected',
                        'admin_response' => $i === 1 ? null : 'تمت مراجعة الحالة مع الطرفين وتبيّن أن العمل اكتمل حسب الاتفاق.',
                        'reviewed_at' => $i === 1 ? null : now()->subDay(),
                    ]);
                }
            }
        }
    }

    private function book(User $customer, VendorService $offer, string $status, int $days, int $i): ?Booking
    {
        $offer->loadMissing('service');
        $minutes = $offer->duration_minutes ?: 60;
        $date = now()->startOfDay()->addDays($status === 'cancelled' ? -$days : $days);

        $hour = 9;

         
        for ($try = 0; $try < 14; $try++) {
            if (in_array($date->dayOfWeek, self::WORK_DAYS, true)) {
                $hour = 9 + ($i + $try) % 2;
                $key = $offer->vendor_id.'|'.$date->toDateString().'|'.$hour;

                $busy = isset($this->taken[$key]) || Booking::whereHas('vendorService', fn ($q) => $q->where('vendor_id', $offer->vendor_id))
                    ->whereDate('booking_date', $date)
                    ->where('start_time', sprintf('%02d:00:00', $hour))
                    ->exists();

                if (! $busy) {
                    $this->taken[$key] = true;
                    break;
                }
            }

            $date = $days < 0 || $status === 'cancelled' ? $date->subDay() : $date->addDay();
        }

        $window = RecurringAvailability::where('vendor_id', $offer->vendor_id)
            ->where('day_of_week', $date->dayOfWeek)
            ->first();

        if (! $window) {
            return null;
        }

        $start = $date->copy()->setTime($hour, 0);
        $end = $start->copy()->addMinutes($minutes);
        $createdAt = $start->isPast() ? $start->copy()->subDays(3)->setTime(18, 0) : now()->subDays($i % 7)->setTime(11 + $i % 8, 15);
        $approvedAt = $createdAt->copy()->addHours(2);

        return Booking::create([
            'customer_id' => $customer->id,
            'vendor_service_id' => $offer->id,
            'availability_id' => $window->id,
            'booking_date' => $date->toDateString(),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
            'service_name' => $offer->service->name,
            'service_description' => $offer->description ?? $offer->service->description,
            'original_price' => $offer->price,
            'status' => $status,
            'approved_at' => in_array($status, ['completed', 'confirmed'], true) ? $approvedAt : null,
            'payment_deadline' => $approvedAt->copy()->addMinutes(30),
            'completed_at' => $status === 'completed' ? $end : null,
            'objection_deadline' => $status === 'completed' ? $end->copy()->addHours(24) : null,
            'cancelled_at' => $status === 'cancelled' ? $createdAt->copy()->addHours(5) : null,
            'cancelled_by' => $status === 'cancelled' ? $customer->id : null,
            'cancellation_penalty' => 0,
            'created_at' => $createdAt,
            'updated_at' => $status === 'completed' ? $end : $createdAt,
        ]);
    }

    private function seedMessages(array $customers): void
    {
        foreach (self::MESSAGES as $index => [$subject, $message]) {
            $sender = $customers[$index % count($customers)];
            $reviewed = $index % 3 === 0;

            ContactMessage::firstOrCreate(['sender_id' => $sender->id, 'subject' => $subject], [
                'message' => $message,
                'status' => $reviewed ? 'reviewed' : 'pending',
                'reviewed_at' => $reviewed ? now()->subDays($index) : null,
                'created_at' => now()->subDays($index * 2 + 1)->setTime(10 + $index, 20),
            ]);
        }
    }

    private function offer(User $vendor, Service $service, float $price, int $minutes, string $description): VendorService
    {
        return VendorService::firstOrCreate(
            ['vendor_id' => $vendor->id, 'service_id' => $service->id],
            [
                'description' => $description,
                'price' => $price,
                'duration_minutes' => $minutes,
                'image' => '',
                'status' => 'active',
            ]
        );
    }

    private function addWorkDays(User $vendor): void
    {
        foreach (self::WORK_DAYS as $day) {
            $exists = RecurringAvailability::where('vendor_id', $vendor->id)->where('day_of_week', $day)->exists();

            if (! $exists) {
                RecurringAvailability::create([
                    'vendor_id' => $vendor->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00:00',
                    'end_time' => '14:00:00',
                    'is_active' => true,
                ]);
            }
        }
    }

     
    private function fillImage(Model $model, string $path): void
    {
        if (blank($model->image)) {
            $model->update(['image' => $path]);
        }
    }

    private function fillPhoto(User $user, string $photo): void
    {
        if (blank($user->profile_image)) {
            $user->update(['profile_image' => "demo/vendors/$photo.jpg"]);
        }
    }

     
    private function phone(string $seed): string
    {
        return '079'.str_pad((string) (abs(crc32($seed)) % 10000000), 7, '0', STR_PAD_LEFT);
    }
}
