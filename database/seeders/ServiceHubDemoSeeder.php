<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Favorite;
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
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ServiceHubDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->renameOldDemoReviewers();

            $this->seed(User::factory()->admin(), [
                'email' => 'admin.seed@servicehub.example',
            ], [
                'first_name' => 'ليلى',
                'last_name' => 'حماد',
                'gender' => 'female',
            ]);

            $customer = $this->seed(User::factory(), [
                'email' => 'customer.seed@servicehub.example',
            ], [
                'first_name' => 'سارة',
                'last_name' => 'الخطيب',
                'gender' => 'female',
            ]);

            $applicant = $this->seed(User::factory(), [
                'email' => 'applicant.seed@servicehub.example',
            ], [
                'first_name' => 'أحمد',
                'last_name' => 'الزعبي',
                'gender' => 'male',
            ]);

            $this->seed(VendorProfile::factory()->pending(), [
                'user_id' => $applicant->id,
            ], [
                'work_type' => 'صيانة الأجهزة المنزلية',
                'bio' => 'خبرة في تشخيص أعطال الأجهزة المنزلية وصيانتها.',
            ]);

            $this->seed(ContactMessage::factory(), [
                'sender_id' => $customer->id,
                'subject' => 'استفسار عن الحجز',
            ], [
                'message' => 'كيف يمكنني تغيير موعد زيارة الفني بعد تأكيد الحجز؟',
            ]);

            $trades = [
                ['plumbing', 'السباكة', 'كشف وإصلاح تسريبات المياه', 'فحص تمديدات المياه وإصلاح الوصلات التالفة.', 'خالد', 'العمري', 40],
                ['electrical', 'الكهرباء', 'فحص لوحة التوزيع', 'فحص القواطع وتحديد أسباب انقطاع التيار.', 'سامر', 'الحداد', 55],
                ['cooling', 'صيانة المكيفات', 'تنظيف وصيانة المكيف', 'تنظيف الفلاتر والوحدة الداخلية وفحص كفاءة التبريد.', 'يزن', 'المومني', 65],
                ['appliances', 'صيانة الأجهزة المنزلية', 'فحص الغسالة', 'تشخيص أعطال الغسالات المنزلية وإصلاحها.', 'محمود', 'الناصر', 45],
                ['carpentry', 'النجارة', 'صيانة الأبواب الخشبية', 'ضبط المفصلات وإصلاح الأبواب والإطارات.', 'مالك', 'سالم', 50],
                ['painting', 'الدهان', 'دهان الجدران الداخلية', 'تجهيز الأسطح وتجديد طلاء الجدران.', 'تامر', 'سعيد', 90],
                ['locks', 'الأقفال', 'استبدال قفل الباب', 'فحص الأقفال وتركيب بدائل ملائمة.', 'فادي', 'حمزة', 35],
                ['flooring', 'الأرضيات', 'إصلاح البلاط', 'استبدال البلاطات المتضررة وإعادة تعبئة الفواصل.', 'عمر', 'حسن', 75],
                ['glass', 'الزجاج والألمنيوم', 'ضبط النوافذ', 'إصلاح مسارات النوافذ ومعالجة تسرب الهواء.', 'نادر', 'عيسى', 60],
                ['insulation', 'العزل', 'فحص العزل المائي', 'معاينة مناطق الرطوبة وتحديد مصدر التسرب.', 'حسام', 'سمير', 80],
                ['heating', 'التدفئة', 'فحص نظام التدفئة', 'تنظيف وحدات التدفئة وفحص كفاءة التشغيل.', 'يوسف', 'مراد', 70],
                ['garden', 'صيانة الحدائق', 'صيانة شبكة الري', 'معالجة انسداد خطوط الري وضبط التوزيع.', 'باسل', 'زيدان', 48],
                ['cleaning', 'التنظيف المتخصص', 'تنظيف بعد أعمال الصيانة', 'إزالة مخلفات العمل وتنظيف الأسطح.', 'سلمى', 'عادل', 58],
                ['solar', 'الطاقة الشمسية', 'فحص الألواح الشمسية', 'معاينة الألواح وقراءة مؤشرات الأداء.', 'ريم', 'منصور', 85],
                ['networks', 'الشبكات المنزلية', 'تشخيص ضعف الاتصال', 'فحص نقاط الشبكة وتحديد سبب ضعف التغطية.', 'سناء', 'مروان', 42],
                ['lighting', 'الإنارة', 'صيانة وحدات الإنارة', 'فحص وحدات الإنارة واستبدال التركيبات المتضررة.', 'ربى', 'صالح', 52],
            ];

            foreach ($trades as [$key, $trade, $name, $description, $firstName, $lastName, $price]) {
                $category = $this->seed(Category::factory(), [
                    'name' => $trade,
                ], [
                    'description' => $description,
                ]);

                $service = $this->seed(Service::factory(), [
                    'category_id' => $category->id,
                    'name' => $name,
                ], [
                    'description' => $description,
                ]);

                $vendor = $this->seed(User::factory()->vendor(), [
                    'email' => "$key.seed@servicehub.example",
                ], [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'gender' => in_array($key, ['cleaning', 'solar', 'networks', 'lighting'], true)
                        ? 'female'
                        : 'male',
                ]);

                $this->seed(VendorProfile::factory(), [
                    'user_id' => $vendor->id,
                ], [
                    'work_type' => $trade,
                    'bio' => $description,
                ]);

                $offer = $this->seed(VendorService::factory(), [
                    'vendor_id' => $vendor->id,
                    'service_id' => $service->id,
                ], [
                    'description' => $description,
                    'price' => $price,
                ]);

                $slot = $this->seed(RecurringAvailability::factory(), [
                    'vendor_id' => $vendor->id,
                    'day_of_week' => 1,
                    'start_time' => '09:00:00',
                ]);

                $project = $this->seed(PortfolioProject::factory(), [
                    'vendor_id' => $vendor->id,
                    'name' => "مشروع $name",
                ], [
                    'description' => $description,
                    'project_price' => $price,
                ]);

                if ($project->project_price === null) {
                    $project->update(['project_price' => $price]);
                }

                $this->seed(PortfolioImage::factory(), [
                    'project_id' => $project->id,
                    'image' => "demo/portfolio/$key.jpg",
                ]);

                if ($key === 'plumbing') {
                    $this->seed(PortfolioImage::factory(), [
                        'project_id' => $project->id,
                        'image' => 'demo/portfolio/plumbing-before.jpg',
                    ]);

                    if ($project->description === $description) {
                        $project->update([
                            'description' => "بدأ العمل بمعاينة الرطوبة أسفل حوض المطبخ. تبيّن أن مصدر التسريب هو وصلة التغذية عند محبس المياه.\n\nشمل الإصلاح استبدال المحبس والوصلة التالفة، ثم تشغيل المياه وفحص نقاط الربط للتأكد من توقف التسريب. توضح صور المشروع حالة التمديدات أثناء الفحص وبعد الإصلاح.",
                        ]);
                    }
                }

                $this->seedBookingActivity($customer, $applicant, $offer, $slot);

                // إنشاء تقييمات متعددة ومختلفة لكل خدمة
                $this->seedExtraServiceReviews($offer, $slot);

                $this->seed(ContactMessage::factory(), [
                    'sender_id' => $customer->id,
                    'subject' => 'استفسار عن '.$trade,
                ], [
                    'message' => 'هل يمكن حجز هذه الخدمة في عطلة نهاية الأسبوع؟',
                ]);

            }
            $this->seedServiceSuggestions();
        });
    }


private function seedServiceSuggestions(): void
{
    $suggestions = [
        [
            'vendor_email' => 'plumbing.seed@servicehub.example',
            'category' => 'السباكة',
            'name' => 'فحص ضغط المياه في المنزل',
            'description' => 'قياس ضغط المياه وفحص أسباب ضعف تدفق المياه داخل المنزل.',
            'price' => 35,
            'duration_minutes' => 60,
            'approval_status' => 'pending',
        ],
        [
            'vendor_email' => 'electrical.seed@servicehub.example',
            'category' => 'الكهرباء',
            'name' => 'فحص استهلاك الكهرباء المنزلي',
            'description' => 'فحص استهلاك الأجهزة الكهربائية وتحديد مصادر الاستهلاك المرتفع.',
            'price' => 45,
            'duration_minutes' => 90,
            'approval_status' => 'pending',
        ],
        [
            'vendor_email' => 'cooling.seed@servicehub.example',
            'category' => 'صيانة المكيفات',
            'name' => 'فحص كفاءة توزيع الهواء',
            'description' => 'فحص توزيع الهواء وتحديد أسباب ضعف التبريد في الغرف.',
            'price' => 40,
            'duration_minutes' => 60,
            'approval_status' => 'rejected',
        ],
    ];

    foreach ($suggestions as $suggestion) {
        $vendor = User::where(
            'email',
            $suggestion['vendor_email']
        )->firstOrFail();

        $category = Category::where(
            'name',
            $suggestion['category']
        )->firstOrFail();

        $service = Service::firstOrCreate(
            [
                'category_id' => $category->id,
                'name' => $suggestion['name'],
            ],
            [
                'description' => $suggestion['description'],
                'image' => '',
                'suggested_by' => $vendor->id,
                'approval_status' => $suggestion['approval_status'],
                'rejection_reason' =>
                    $suggestion['approval_status'] === 'rejected'
                        ? 'يرجى توضيح تفاصيل الخدمة بشكل أكبر.'
                        : null,
                'is_deleted' => false,
                'is_active' => false,
            ]
        );

        // لا نغيّر الخدمات الموجودة مسبقًا أو أصحابها.
        if ((int) $service->suggested_by !== (int) $vendor->id) {
            continue;
        }

        VendorService::firstOrCreate(
            [
                'vendor_id' => $vendor->id,
                'service_id' => $service->id,
            ],
            [
                'description' => $suggestion['description'],
                'price' => $suggestion['price'],
                'duration_minutes' => $suggestion['duration_minutes'],
                'image' => '',
                'status' =>
                    $suggestion['approval_status'] === 'rejected'
                        ? 'rejected'
                        : 'pending',
                'rejection_reason' =>
                    $suggestion['approval_status'] === 'rejected'
                        ? 'يرجى توضيح تفاصيل الخدمة بشكل أكبر.'
                        : null,
            ]
        );
    }
}


    private function seedBookingActivity(
        User $customer,
        User $applicant,
        VendorService $offer,
        RecurringAvailability $slot,
    ): void {
        // إزالة الحجز التجريبي المعلّق السابق حتى لا يحجب وقتًا من المواعيد المتاحة
        Booking::where('customer_id', $customer->id)
            ->where('vendor_service_id', $offer->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $booking = $this->seed(Booking::factory(), [
            'customer_id' => $customer->id,
            'vendor_service_id' => $offer->id,
            'booking_date' => Carbon::now()->subDays(90)->toDateString(),
            'status' => 'completed',
        ], [
            'availability_id' => $slot->id,
        ]);

        $this->seed(Payment::factory(), [
            'booking_id' => $booking->id,
        ]);

        $this->seed(WalletTransaction::factory(), [
            'booking_id' => $booking->id,
            'vendor_id' => $offer->vendor_id,
            'transaction_type' => 'credit',
        ], [
            'amount' => $booking->original_price,
            'description' => 'تحرير كامل مبلغ الحجز',
        ]);

        $this->seed(Review::factory(), [
            'booking_id' => $booking->id,
            'reviewer_id' => $customer->id,
            'reviewed_user_id' => $offer->vendor_id,
        ], [
            'rating' => 5,
            'comment' => 'وصل الفني في الموعد وأنجز العمل بعناية.',
        ]);

        $this->seed(Review::factory()->fromVendor(), [
            'booking_id' => $booking->id,
            'reviewer_id' => $offer->vendor_id,
            'reviewed_user_id' => $customer->id,
        ], [
            'rating' => 5,
            'comment' => 'كان التواصل واضحًا وكان الموقع جاهزًا للعمل.',
        ]);

        $this->seed(Favorite::factory(), [
            'customer_id' => $customer->id,
            'vendor_service_id' => $offer->id,
        ]);

        $this->seed(Notification::factory(), [
            'user_id' => $customer->id,
            'title' => "اكتمل حجز $booking->service_name",
        ], [
            'message' => 'يمكنك مراجعة تفاصيل الحجز وتقييم الخدمة.',
        ]);

        $this->seed(Notification::factory(), [
            'user_id' => $offer->vendor_id,
            'title' => 'تم تحرير مبلغ الحجز رقم '.$booking->id,
        ], [
            'type' => 'wallet',
            'message' => 'أضيف مبلغ الحجز إلى السجل المالي بعد انتهاء مهلة المراجعة.',
        ]);

        $disputed = $this->seed(Booking::factory(), [
            'customer_id' => $applicant->id,
            'vendor_service_id' => $offer->id,
            'booking_date' => Carbon::now()->subDays(60)->toDateString(),
            'status' => 'completed',
        ], [
            'availability_id' => $slot->id,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        $this->seed(Payment::factory(), [
            'booking_id' => $disputed->id,
        ]);

        $this->seed(RefundRequest::factory(), [
            'booking_id' => $disputed->id,
        ], [
            'reason' => 'عاد العطل بعد انتهاء الزيارة وأطلب مراجعة الحجز.',
        ]);
    }

    private function seedExtraServiceReviews(
        VendorService $offer,
        RecurringAvailability $slot
    ): void {
        $comments = [
            'الخدمة كانت مرتبة والفني شرح لي تفاصيل العمل.',
            'وصل في الموعد وأنجز المطلوب باهتمام.',
            'تم حل المشكلة بسرعة وبنتيجة جيدة.',
        ];

        foreach ($comments as $index => $comment) {
            $number = $index + 1;

            // استخدام تاريخ ثابت يمنع تكرار الحجوزات عند تشغيل الـSeeder مرة أخرى
            $bookingDate = Carbon::create(2025, 1, 6)
                ->addWeeks($number)
                ->toDateString();

            $customer = $this->seed(User::factory(), [
                'email' => "review.{$offer->id}.{$number}@servicehub.example",
            ], [
                'first_name' => 'عميل',
                'last_name' => 'تجريبي '.$number,
            ]);

            $customer->update([
                'first_name' => fake()->randomElement([
                    'مها', 'ليان', 'سارة', 'نور', 'رنا', 'دانا', 'عمر', 'يزن', 'آدم', 'خالد',
                ]),
                'last_name' => fake()->randomElement([
                    'الخطيب', 'العمري', 'الزعبي', 'الحسن', 'العبادي', 'الرفاعي', 'النجار', 'سالم',
                ]),
                'gender' => $number % 2 === 0 ? 'female' : 'male',
            ]);

            $booking = $this->seed(Booking::factory(), [
                'customer_id' => $customer->id,
                'vendor_service_id' => $offer->id,
                'booking_date' => $bookingDate,
                'status' => 'completed',
            ], [
                'availability_id' => $slot->id,
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
            ]);

            $this->seed(Payment::factory(), [
                'booking_id' => $booking->id,
            ]);

            Review::updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'reviewer_id' => $customer->id,
                ],
                [
                    'reviewed_user_id' => $offer->vendor_id,
                    'rating' => $number % 4 === 0 ? 4 : 5,
                    'comment' => $comment,
                ]
            );
        }
    }

    private function renameOldDemoReviewers(): void
    {
        $firstNames = [
            'مها', 'ليان', 'سارة', 'نور', 'رنا', 'دانا', 'عمر', 'يزن', 'آدم', 'خالد',
        ];
        $lastNames = [
            'الخطيب', 'العمري', 'الزعبي', 'الحسن', 'العبادي', 'الرفاعي', 'النجار', 'سالم',
        ];

        User::query()
            ->where(function ($query) {
                $query->where('email', 'like', 'review.%@servicehub.example')
                    ->orWhere(function ($query) {
                        $query->where('first_name', 'عميل')
                            ->where('last_name', 'like', 'تجريبي%');
                    });
            })
            ->get()
            ->each(function (User $user) use ($firstNames, $lastNames) {
                $user->update([
                    'first_name' => fake()->randomElement($firstNames),
                    'last_name' => fake()->randomElement($lastNames),
                ]);
            });
    }

    /**
     * @template TModel of Model
     *
     * @param Factory<TModel> $factory
     * @return TModel
     */
    private function seed(
        Factory $factory,
        array $identity,
        array $attributes = []
    ): Model {
        $model = $factory->newModel();
        $query = $model->newQuery();
        foreach ($identity as $column => $value) {
            if ($model instanceof Booking && $column === 'booking_date') {
                $query->whereDate($column, $value);
            } else {
                $query->where($column, $value);
            }
        }

        return $query->firstOr(
            fn () => $factory->create($identity + $attributes),
        );
    }
}
