# التوثيق الفني والوظيفي لمشروع صلّحها

هذا التوثيق يصف نسخة المشروع المحلية بتاريخ 25 تشرين الثاني 2026. راجع [README.md](README.md) لخطوات التثبيت والتشغيل. دليل المسارات وفهرس ملفات الكود في آخر الوثيقة مستخرجان من المشروع الحالي.

## 1. بنية التطبيق

صلّحها منصة عربية (RTL) لحجز خدمات الصيانة المنزلية. التطبيق مبني على Laravel 12 وPHP، ويستخدم MySQL أو MariaDB، وقوالب Blade، وJavaScript وCSS. الاسم المعروض مصدره `site_name` في `config/app.php` ويظهر في القالب العام وشعار التنقل والتذييل والفواتير.

مسار الطلب المعتاد يبدأ بتعريفه في `routes/web.php`، ثم يمر عبر الوسائط المرتبطة به، ثم يتحقق الكنترولر من المدخلات ويقرأ أو يعدّل السجلات. صفحات HTML تُعرض بقوالب Blade، والـAPI ومساعد الصيانة يرجعان JSON عند المسارات المخصصة لذلك.

- `DB::table(...)` يبني استعلامًا مباشرًا على الجدول. `Model::query()` يبني استعلام Eloquent يتيح علاقات الموديل وإعداداته. التنفيذ يتم عند عمليات مثل `get()` أو `paginate()`.
- القالب `pages/public/directory.blade.php` يعرض الأقسام أو الفنيين حسب البيانات الممرّرة له. المكوّن `x-category-card` يستقبل بيانات القسم عبر `:category` ويضع رقم القسم في رابط `categories.show`. `components/pager` يعرض أزرار الصفحات.
- رابط `/index` يعيد التوجيه إلى `/`. تفاصيل كل مسار واسمه والكنترولر موجودة في الملحق.

## 2. المستخدمون والصلاحيات

| المستخدم | الوظائف |
| --- | --- |
| الزائر | الرئيسية، الأقسام، دليل الفنيين، الخدمات، تفاصيل العروض، الأسئلة الشائعة، التواصل، المساعد، الدخول والتسجيل والاستعادة |
| العميل | المفضّلة والإشعارات والملف الشخصي، الحجز والسجل والفواتير، التقييم والاعتراض، والتقديم للانضمام كفني |
| الفني | النظرة العامة، طلبات الحجز، التوفّر الأسبوعي، إدارة العروض والاقتراحات، معرض الأعمال، التقييمات والسجل المالي |
| الأدمن | النظرة العامة، المستخدمون وطلبات الانضمام، الأقسام وكتالوج الخدمات واعتماد العروض، الحجوزات والإلغاءات والمدفوعات والاسترداد والمحفظة والتقييمات ورسائل التواصل |

`ActiveAdmin` و`ActiveVendor` يضبطان الوصول للمسارات المخصصة لكل دور. بعض العمليات العامة للمستخدم المسجل لها شروطها الخاصة داخل الكنترولر. مثال ذلك أن الحساب النشط يستطيع طلب خدمة فني آخر وفق شروط مسار الحجز، ولا يستطيع حجز عرضه الخاص. تسجيل دخول الأدمن يوجّه إلى لوحة الإدارة.

التسجيل والاستعادة والدخول يستخدمون قواعد كلمة المرور المعتمدة في `StrongPassword`. الحد الأدنى 8 أحرف والحد الأعلى 72 بايت، مع حرف إنجليزي كبير وصغير ورقم ورمز. الملف الشخصي يوفّر تعديل المعلومات والصورة حسب الدور. التصريح يتحقق منه السيرفر ويُخزّن بخصوصية، ومسار عرضه للإدارة مضبوط بالصلاحيات.

مسارات API للإدارة تستخدم Sanctum بعد تسجيل الدخول، وتشمل لوحة الإدارة وإلغاء التوكن. راجع وسيط كل مسار في الملحق.

## 3. دورة الحجز

1. `MarketController` يعرض أوقات فترات التوفّر الفعّالة خلال 30 يومًا، بدءًا من يوم عمّان الحالي. الأوقات التي تشغلها حجوزات أخرى تظهر كخيارات محجوزة وغير قابلة للاختيار.
2. وقت البداية المعروض يزيد كل 30 دقيقة من بداية فترة الفني، ونهاية الخيار محسوبة حسب مدة الخدمة. إذا لم يحدد العرض مدة، يُستخدم 60 دقيقة.
3. `BookingProfileController` يتحقق من قيمة الموعد `YYYY-MM-DD|HH:MM|availability_id`، ويرفض التواريخ والساعات غير الصالحة والمواعيد الماضية أو خارج الأيام المسموحة أو شبكة المواعيد أو فترة الفني. يُفحص التداخل قبل كتابة الحجز.
4. الفني يقبل أو يرفض الطلب المعلق. عند القبول يبدأ عدّاد دفع 30 دقيقة. الدفع **تجريبي**، وتظهر الفاتورة بعد الدفع حسب صلاحية العرض.
5. تأكيد اكتمال الخدمة يتم من الطرفين بعد نهاية الموعد لحجز مدفوع. يفتح اكتمال الخدمة مهلة اعتراض 24 ساعة للعميل.
6. الإلغاء له شروط مرتبطة بالحالة، وقت الموعد والدفع. اطلع على `Booking::canBeCancelled` و`BookingProfileController::cancel` للحسابات التفصيلية.

التوفّر الأسبوعي يتحقق من صيغة الساعة ومن أن النهاية بعد البداية في نفس اليوم. الفترات الفعّالة لنفس الفني لا تتداخل. الفترات المرتبطة بحجوزات قادمة تشغل الموعد محمية عند تعديلها أو تعطيلها. أوقات المواعيد المحلية تُفسَّر بتوقيت `Asia/Amman`، وإعداد توقيت التطبيق العام يبقى مستقلًا.

## 4. المال، الإشعارات والإدارة

- `PaymentController` يتحقق من إمكانية الدفع ويكتب سجل الدفعة ويعرض الفاتورة. نموذج الدفع للتجربة فقط.
- `BookingCompletionController` يدير التأكيد والتقييم والاعتراض. `AdminRefundsController` يراجع الاعتراض، ويمنع تعديل قرار سبق حسمه أو تنفيذ حركة مالية سبق تسجيلها. قبول الاعتراض يسجل استرداد 70٪ للعميل وحصة 15٪ للفني حسب سياسة الكود، ورفضه يسجل صرف 85٪ للفني إذا انطبقت الشروط.
- `VendorSettlement` يحدد الحجوزات المكتملة والمدفوعة التي صارت مستحقة بعد انتهاء مهلة الاعتراض، ويكتب حركة المحفظة. `routes/console.php` يشغّل تحرير المستحقات وإنهاء الحجوزات غير المدفوعة عبر الجدولة.
- صفحات المحفظة تقرأ الحركات من قاعدة البيانات. الإشعارات والمفضّلة ورسائل التواصل لها جداول وكنترولرات مستقلة.
- حذف مستخدم من الإدارة **حذف نهائي**. حذف حساب الأدمن محمي. عملية الحذف تفصل مرجع `services.suggested_by` قبل حذف المستخدم، وتطبق قاعدة البيانات قواعد العلاقات المحددة في الترحيلات. حذف أو أرشفة السجلات الأخرى يعتمد على آلية كل نموذج، لذلك يُراجع الكنترولر والترحيل قبل افتراض أثر الحذف.

## 5. التحقق وإدارة المحتوى

| الحقل أو العملية | القاعدة |
| --- | --- |
| سنوات الخبرة | رقم صحيح من 0 إلى 80 |
| تاريخ انتهاء تصريح العمل | تاريخ صالح من اليوم حتى `9999-12-31` |
| تاريخ إنجاز المشروع | اختياري من `1900-01-01` إلى اليوم |
| مدة العرض | اختيارية، عدد صحيح من 5 إلى 1440 دقيقة |
| فلاتر التاريخ | تاريخ بصيغة `Y-m-d`، وتاريخ النهاية يساوي البداية أو يأتي بعدها إذا عُبّيت القيمتان |
| صور المشروع | من صورة إلى 8 صور عند الإنشاء، وإدارة الإضافة والإزالة ضمن التعديل. JPG أو PNG أو WebP حتى 5 ميغابايت للصورة |
| الحسابات والخدمات | يُتحقق من الدور والملكية والحالة قبل الإجراءات التي تتطلب ذلك |

الفني يدير العرض والسعر والمدة والوصف، ويقدّم اقتراح خدمة عند الحاجة. الإدارة تراجع الطلب وتتحكم في الكتالوج؛ ظهور العرض للزائر مربوط بحالة السجلات وموافقتها وفق قواعد الموديل والكنترولر. كارد المشروع فيه رابط تفاصيل، وإدارة الصور ضمن نموذج التعديل.

## 6. الواجهة وتجربة الاستخدام

- الرئيسية فيها هيرو بصور خلفية متبدّلة ونص ثابت، وأقسام الخدمات والفنيين وكيف تعمل المنصة والأسئلة الشائعة. نموذج التواصل يظهر في النسخة الحالية على صفحة `/contact`.
- التنقل مشترك بين القوالب. زر قائمة الهاتف على اليسار، وقائمة الحساب تُضبط قرب الصورة ضمن عرض الشاشة. القوائم الطويلة تمرَّر داخليًا في الشاشات القصيرة.
- لوحة الفني والإدارة تعرض رسوم الحجوزات وحالاتها. السكربت المشترك للرسوم في `components/booking-charts-script.blade.php`. واجهة الفني تعيد توزيع الرسوم على الشاشات الضيقة.
- الاسترداد على الموبايل يعرض إرشادًا للسحب الأفقي، واسم العميل يبقى ثابتًا عند السحب. عرض عمود الاسم يتكيّف مع محتوى الأسماء ويعرضها على سطر واحد.
- النوافذ المنبثقة تشمل قرارات الانضمام وحذف المستخدم والعروض والصور وإجراءات الحجز والاسترداد. أدوات إضافة الملفات تستخدم مكوّنات الواجهة المشتركة.
- `public/assets/theme.js` يدير السلايدر وموضع قائمة الحساب وقياسات الشريط الجانبي. `servicehub-pages.js` يدير تفاعلات الصفحات المشتركة ومنها المساعد وحقول الصور. ترتيب تحميل ملفات الواجهة موجود في `resources/views/layouts/app.blade.php`.

## 7. مساعد الصيانة

المسار `POST /maintenance-assistant` يستقبل وصفًا من 500 حرف كحد أقصى، وسجلًا محدودًا للأسئلة والأجوبة. المساعد يحاول مطابقة الوصف مع أقسام الموقع، ويستخدم Gemini إذا ضُبطت مفاتيحه واحتاج التصنيف إليه. الأسئلة المتتابعة تتجنب تكرار السؤال وتستخدم الوصف الأول والإجابات السابقة. إذا ما كفت التفاصيل بعد ثلاثة أسئلة، يعرض الخيارات الأقرب أو رابط تصفّح الأقسام. زر «مشكلة ثانية» يصفّر المحادثة.

مثال «الكنباي مكسورة» يقترح النجارة إذا القسم موجود، وطلب إصلاح كنباية دون تحديد الضرر يدفع المساعد للسؤال عن الخشب أو القماش. مشكلة القماش توجّه للتنجيد، وإذا القسم غير متاح يوضّح ذلك. حالات الخطر المباشر لها تنبيه، والاقتراحات تربط بأقسام الموقع وخدماتها المتاحة.

## 8. البيانات والتشغيل والتحقق

- جداول الحسابات والملفات المهنية مرتبطة بالأقسام والخدمات وعروض الفنيين. الحجوزات تربط العميل بعرض فني وفترة توفّر، وتتبعها المدفوعات والاعتراضات والتقييمات وحركات المحفظة. راجع ملفات `database/migrations` لتفاصيل الأعمدة والقيود وترتيب تطبيقها.
- `DatabaseSeeder` يشغّل `ServiceHubDemoSeeder`. `PresentationSeeder` يضيف مجموعة العرض الموسّعة. ملفات SQL التجريبية واردة في الفهرس؛ تُستخدم على قاعدة تطوير مقصودة بعد الترحيلات.
- خطوات XAMPP وMySQL وأوامر التشغيل موجودة في [README.md](README.md). استخدم قاعدة معزولة للاختبارات دائمًا. اختبارات المشروع تشمل الوصول حسب الدور، الحجز، المساعد، الحسابات، الإدارة والدفعات. آخر تشغيل موثّق في المحادثة نجح فيه 61 اختبارًا على MySQL؛ العدد ليس ضمانًا لإصدار لاحق.
- اختيار قاعدة بيانات فارغة، تشغيل الترحيلات، إعداد مجلد التخزين والجدولة خطوات مستقلة. تشغيل السيدر وحده لا يعيد بناء قاعدة البيانات.

## الملحق أ. المسارات الفعلية

المسارات الآتية مستخرجة من Laravel. علامة — تشير إلى قيمة غير معرّفة في قائمة المسارات.

| الطريقة | المسار | الاسم | المعالج | الوسائط |
| --- | --- | --- | --- | --- |
| GET/HEAD | `/` | `home` | `App\Http\Controllers\HomeController` | web |
| GET/HEAD | `/AdminDashboard` | `—` | `App\Http\Controllers\AdminDashboardController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/about` | `about` | `Illuminate\Routing\ViewController` | web |
| GET/HEAD | `/admin/Users` | `admin.admin.Users` | `App\Http\Controllers\AdminUsersController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| POST | `/admin/addServices` | `admin.addServices` | `App\Http\Controllers\AdminServicesController@addServices` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/admin-applications` | `admin.admin-applications` | `App\Http\Controllers\AdminVendorApplicationController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/admin-applications/{application}/permit` | `admin.applications.permit` | `App\Http\Controllers\AdminVendorApplicationController@permit` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| PUT | `/admin/admin-applications/{id}/approved` | `admin.admin-applications.approved` | `App\Http\Controllers\AdminVendorApplicationController@approved` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| PUT | `/admin/admin-applications/{id}/rejected` | `admin.admin-applications.rejected` | `App\Http\Controllers\AdminVendorApplicationController@rejected` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/admin-cancellations` | `admin.cancellations.index` | `App\Http\Controllers\AdminCancellationController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| DELETE | `/admin/admin-cancellations/{id}` | `admin.cancellations.destroy` | `App\Http\Controllers\AdminCancellationController@destroy` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/admin-refunds` | `admin.refunds.index` | `App\Http\Controllers\AdminRefundsController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| POST | `/admin/admin-refunds/{refund}` | `admin.refunds.decide` | `App\Http\Controllers\AdminRefundsController@decide` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/admin-reviews` | `admin.reviews.index` | `App\Http\Controllers\AdminReviewController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| PUT | `/admin/admin-reviews/{id}` | `admin.reviews.update` | `App\Http\Controllers\AdminReviewController@update` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| DELETE | `/admin/admin-reviews/{id}` | `admin.reviews.destroy` | `App\Http\Controllers\AdminReviewController@destroy` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/admin-services` | `admin.admin-services` | `App\Http\Controllers\AdminServicesController@adminServices` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| PUT | `/admin/admin-services/toggle/{service}` | `admin.toggleServices` | `App\Http\Controllers\AdminServicesController@toggleServices` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| PUT | `/admin/admin-services/{service}` | `admin.editServices` | `App\Http\Controllers\AdminServicesController@editServices` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| DELETE | `/admin/admin-services/{service}` | `admin.deleteServices` | `App\Http\Controllers\AdminServicesController@deleteServices` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/admin-services/{service}/image` | `admin.services.image` | `App\Http\Controllers\AdminServicesController@image` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/admin-wallet` | `admin.wallet.index` | `App\Http\Controllers\AdminDashboardController@wallet` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/bookings` | `admin.bookings.index` | `App\Http\Controllers\AdminBookingController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/bookings/{booking}` | `admin.bookings.show` | `App\Http\Controllers\AdminBookingController@show` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| DELETE | `/admin/bookings/{booking}` | `admin.bookings.destroy` | `App\Http\Controllers\AdminBookingController@destroy` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/categories` | `admin.categories` | `App\Http\Controllers\CategoryController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| POST | `/admin/categories` | `admin.categories.store` | `App\Http\Controllers\CategoryController@store` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/categories/create` | `admin.categories.create` | `App\Http\Controllers\CategoryController@create` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| PUT | `/admin/categories/{category}` | `admin.categories.update` | `App\Http\Controllers\CategoryController@update` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| DELETE | `/admin/categories/{category}` | `admin.categories.destroy` | `App\Http\Controllers\CategoryController@destroy` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/categories/{category}/edit` | `admin.categories.edit` | `App\Http\Controllers\CategoryController@edit` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/dashboard` | `admin.dashboard` | `App\Http\Controllers\AdminDashboardController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| DELETE | `/admin/deleteUser/{user}` | `admin.users.destroy` | `App\Http\Controllers\AdminUsersController@destroy` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/messages` | `admin.messages.index` | `App\Http\Controllers\AdminMessageController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/messages/{message}` | `admin.messages.show` | `App\Http\Controllers\AdminMessageController@show` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| PATCH | `/admin/messages/{message}` | `admin.messages.update` | `App\Http\Controllers\AdminMessageController@update` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/payments` | `admin.payments.index` | `App\Http\Controllers\AdminPaymentController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/payments/{payment}` | `admin.payments.show` | `App\Http\Controllers\AdminPaymentController@show` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| PUT | `/admin/users/{user}/toggle-status` | `admin.users.toggle-status` | `App\Http\Controllers\AdminUsersController@toggleStatus` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/admin/vendor-service-requests` | `admin.service-requests.index` | `App\Http\Controllers\AdminServiceApprovalController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| POST | `/admin/vendor-service-requests/{offer}` | `admin.service-requests.decide` | `App\Http\Controllers\AdminServiceApprovalController@decide` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/api/admin/dashboard` | `—` | `App\Http\Controllers\Api\AdminDashboardController@index` | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\ActiveAdmin |
| POST | `/api/admin/login` | `—` | `App\Http\Controllers\Api\AdminAuthController@login` | api, Illuminate\Routing\Middleware\ThrottleRequests:5,1 |
| POST | `/api/admin/logout` | `—` | `App\Http\Controllers\Api\AdminAuthController@logout` | api, Illuminate\Auth\Middleware\Authenticate:sanctum, App\Http\Middleware\ActiveAdmin |
| GET/HEAD | `/api/user` | `—` | `Closure` | api, Illuminate\Auth\Middleware\Authenticate:sanctum |
| GET/HEAD | `/become-vendor` | `vendor.apply` | `App\Http\Controllers\VendorApplicationController@show` | web |
| POST | `/become-vendor` | `—` | `App\Http\Controllers\VendorApplicationController@store` | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | `/booking` | `—` | `Illuminate\Routing\RedirectController` | web |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | `/booking-details` | `—` | `Illuminate\Routing\RedirectController` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD | `/bookings` | `customer.bookings` | `App\Http\Controllers\ProfileController@bookings` | web, Illuminate\Auth\Middleware\Authenticate |
| POST | `/bookings/{booking}/cancel` | `booking.cancel` | `App\Http\Controllers\BookingProfileController@cancel` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD | `/bookings/{booking}/checkout` | `payment.checkout` | `App\Http\Controllers\PaymentController@checkout` | web, Illuminate\Auth\Middleware\Authenticate |
| POST | `/bookings/{booking}/completion` | `booking.completion` | `App\Http\Controllers\BookingCompletionController@confirm` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD | `/bookings/{booking}/invoice` | `payment.invoice` | `App\Http\Controllers\PaymentController@invoice` | web, Illuminate\Auth\Middleware\Authenticate |
| POST | `/bookings/{booking}/objection` | `booking.objection` | `App\Http\Controllers\BookingCompletionController@object` | web, Illuminate\Auth\Middleware\Authenticate |
| POST | `/bookings/{booking}/payment` | `payment.store` | `App\Http\Controllers\PaymentController@store` | web, Illuminate\Auth\Middleware\Authenticate |
| POST | `/bookings/{booking}/review` | `booking.review` | `App\Http\Controllers\BookingCompletionController@review` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD | `/categories` | `categories.index` | `App\Http\Controllers\HomeController@categories` | web |
| GET/HEAD | `/categories/{category}` | `categories.show` | `App\Http\Controllers\HomeController@category` | web |
| GET/HEAD | `/contact` | `contact` | `Illuminate\Routing\ViewController` | web |
| POST | `/contact` | `contact.store` | `App\Http\Controllers\ProfileController@sendMessage` | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:6,1 |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | `/customer-bookings` | `—` | `Illuminate\Routing\RedirectController` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | `/customer-favorites` | `—` | `Illuminate\Routing\RedirectController` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD | `/customer-messages` | `customer.messages` | `App\Http\Controllers\ProfileController@messages` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD | `/customer-notifications` | `customer.notifications` | `App\Http\Controllers\ProfileController@notifications` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD | `/customer-reviews` | `customer.reviews` | `App\Http\Controllers\ProfileController@reviews` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | `/customer-settings` | `—` | `Illuminate\Routing\RedirectController` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD | `/faq` | `faq` | `Illuminate\Routing\ViewController` | web |
| GET/HEAD | `/favorites` | `customer.favorites` | `App\Http\Controllers\ProfileController@favorites` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | `/forgot-password` | `—` | `Illuminate\Routing\RedirectController` | web |
| GET/HEAD | `/how-it-works` | `how-it-works` | `Illuminate\Routing\ViewController` | web |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | `/index` | `—` | `Illuminate\Routing\RedirectController` | web |
| GET/HEAD | `/login` | `login` | `App\Http\Controllers\Auth\LoginController@showLoginForm` | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated |
| POST | `/login` | `—` | `App\Http\Controllers\Auth\LoginController@login` | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated |
| POST | `/logout` | `logout` | `App\Http\Controllers\Auth\LoginController@logout` | web, Illuminate\Auth\Middleware\Authenticate |
| POST | `/maintenance-assistant` | `maintenance.assistant` | `App\Http\Controllers\MaintenanceAssistantController` | web, Illuminate\Routing\Middleware\ThrottleRequests:8,1 |
| PATCH | `/my-profile` | `account.details.update` | `App\Http\Controllers\AccountDetailsController` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD | `/my-profile` | `account.profile` | `Closure` | web, Illuminate\Auth\Middleware\Authenticate |
| POST | `/my-profile/photo` | `account.picture.update` | `App\Http\Controllers\AccountPictureController@update` | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| DELETE | `/my-profile/photo` | `account.picture.destroy` | `App\Http\Controllers\AccountPictureController@destroy` | web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| GET/HEAD | `/notifications` | `notifications.index` | `App\Http\Controllers\NotificationController@index` | web, Illuminate\Auth\Middleware\Authenticate |
| POST | `/notifications/{notification}/read` | `notifications.read` | `App\Http\Controllers\NotificationController@read` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD | `/password/confirm` | `password.confirm` | `App\Http\Controllers\Auth\ConfirmPasswordController@showConfirmForm` | web, Illuminate\Auth\Middleware\Authenticate |
| POST | `/password/confirm` | `—` | `App\Http\Controllers\Auth\ConfirmPasswordController@confirm` | web, Illuminate\Auth\Middleware\Authenticate |
| POST | `/password/email` | `password.email` | `App\Http\Controllers\Auth\ForgotPasswordController@sendResetLinkEmail` | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| GET/HEAD | `/password/reset` | `password.request` | `App\Http\Controllers\Auth\ForgotPasswordController@showLinkRequestForm` | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated |
| POST | `/password/reset` | `password.update` | `App\Http\Controllers\Auth\ResetPasswordController@reset` | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| GET/HEAD | `/password/reset/{token}` | `password.reset` | `App\Http\Controllers\Auth\ResetPasswordController@showResetForm` | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated |
| GET/HEAD | `/profile-pictures/{account}` | `account.picture.show` | `App\Http\Controllers\AccountPictureController@show` | web |
| GET/HEAD | `/providers/{vendor}` | `provider.show` | `Closure` | web |
| GET/HEAD | `/register` | `register` | `App\Http\Controllers\Auth\RegisterController@showRegistrationForm` | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated |
| POST | `/register` | `—` | `App\Http\Controllers\Auth\RegisterController@register` | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| GET/HEAD | `/service-details` | `—` | `Closure` | web |
| GET/HEAD | `/services` | `market.index` | `App\Http\Controllers\MarketController@index` | web |
| GET/HEAD | `/services/{vendorService}` | `market.show` | `App\Http\Controllers\MarketController@show` | web |
| POST | `/services/{vendorService}/book` | `booking.store` | `App\Http\Controllers\BookingProfileController@store` | web, Illuminate\Auth\Middleware\Authenticate |
| POST | `/services/{vendorService}/favorite` | `favorites.toggle` | `App\Http\Controllers\FavoriteController@toggle` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD | `/settings` | `customer.settings` | `App\Http\Controllers\ProfileController@settings` | web, Illuminate\Auth\Middleware\Authenticate |
| PUT | `/settings` | `customer.settings.update` | `App\Http\Controllers\ProfileController@updateSettings` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD | `/users/{user}/profile` | `users.profile.show` | `App\Http\Controllers\UserPublicProfileController@show` | web, Illuminate\Auth\Middleware\Authenticate |
| GET/HEAD | `/vendor-availability` | `vendor.availability.index` | `App\Http\Controllers\VendorAvailabilityController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| POST | `/vendor-availability` | `vendor.availability.store` | `App\Http\Controllers\VendorAvailabilityController@store` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| PATCH | `/vendor-availability/{availability}` | `vendor.availability.update` | `App\Http\Controllers\VendorAvailabilityController@update` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| DELETE | `/vendor-availability/{availability}` | `vendor.availability.destroy` | `App\Http\Controllers\VendorAvailabilityController@destroy` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| GET/HEAD | `/vendor-bookings` | `vendor.vendor-bookings.index` | `App\Http\Controllers\BookingController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| PUT/PATCH | `/vendor-bookings/{booking}` | `vendor.vendor-bookings.update` | `App\Http\Controllers\BookingController@update` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| GET/HEAD | `/vendor-dashboard` | `vendor.dashboard` | `App\Http\Controllers\VendorPagesController@dashboard` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | `/vendor-portfolio` | `vendor.portfolio.redirect` | `Illuminate\Routing\RedirectController` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| POST | `/vendor-portfolio` | `vendor.portfolio.store` | `App\Http\Controllers\PortfolioController@store` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | `/vendor-portfolio-form` | `vendor.portfolio-form.redirect` | `Illuminate\Routing\RedirectController` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| GET/HEAD | `/vendor-portfolio-images/{image}` | `vendor.portfolio.image` | `App\Http\Controllers\PortfolioController@image` | web |
| DELETE | `/vendor-portfolio-images/{image}` | `vendor.portfolio.image.destroy` | `App\Http\Controllers\PortfolioController@destroyImage` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| GET/HEAD | `/vendor-portfolio/{project}` | `vendor.portfolio.show` | `App\Http\Controllers\PortfolioController@show` | web |
| PATCH | `/vendor-portfolio/{project}` | `vendor.portfolio.update` | `App\Http\Controllers\PortfolioController@update` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| DELETE | `/vendor-portfolio/{project}` | `vendor.portfolio.destroy` | `App\Http\Controllers\PortfolioController@destroy` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| GET/HEAD | `/vendor-profile` | `vendor.profile` | `App\Http\Controllers\VendorPagesController@profile` | web |
| GET/HEAD | `/vendor-reviews` | `vendor.reviews` | `App\Http\Controllers\VendorReviewController@index` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| POST | `/vendor-reviews/{booking}` | `vendor.reviews.store` | `App\Http\Controllers\VendorReviewController@store` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| PATCH | `/vendor-reviews/{booking}` | `vendor.reviews.update` | `App\Http\Controllers\VendorReviewController@update` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| DELETE | `/vendor-reviews/{booking}` | `vendor.reviews.destroy` | `App\Http\Controllers\VendorReviewController@destroy` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | `/vendor-service-form` | `vendor.service-form.redirect` | `Illuminate\Routing\RedirectController` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| POST | `/vendor-service-requests` | `vendor.service-requests.store` | `App\Http\Controllers\VendorServiceRequestController@store` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| PATCH | `/vendor-service-requests/{offer}` | `vendor.service-requests.update` | `App\Http\Controllers\VendorServiceRequestController@update` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| DELETE | `/vendor-service-requests/{offer}` | `vendor.service-requests.destroy` | `App\Http\Controllers\VendorServiceRequestController@destroy` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| POST | `/vendor-service-suggestions` | `vendor.service-suggestions.store` | `App\Http\Controllers\VendorServiceRequestController@suggest` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | `/vendor-services` | `vendor.services.redirect` | `Illuminate\Routing\RedirectController` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| GET/HEAD | `/vendor-services/{offer}/image` | `vendor.service.image` | `App\Http\Controllers\VendorPagesController@serviceImage` | web |
| GET/HEAD | `/vendor-services/{offer}/reviews` | `vendor.service.reviews` | `App\Http\Controllers\VendorPagesController@serviceReviews` | web |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | `/vendor-settings` | `vendor.settings.redirect` | `Illuminate\Routing\RedirectController` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| GET/HEAD | `/vendor-wallet` | `vendor.wallet` | `App\Http\Controllers\VendorWalletController` | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ActiveVendor |
| GET/HEAD | `/vendors` | `vendors.index` | `App\Http\Controllers\HomeController@vendors` | web |

## الملحق ب. فهرس ملفات المشروع

يشمل الفهرس ملفات التطبيق والقوالب والإعدادات والترحيلات والسيدرز والاختبارات وملفات الواجهة. الأسماء بعد ملف PHP تشير إلى الدوال أو الاختبارات المعلنة فيه. الصور وملفات الحزم والتخزين والبيئة المحلية خارج الفهرس.

### app

- [`app/Http/Controllers/AccountDetailsController.php`](app/Http/Controllers/AccountDetailsController.php) — `__invoke`
- [`app/Http/Controllers/AccountPictureController.php`](app/Http/Controllers/AccountPictureController.php) — `show`، `update`، `destroy`، `finishChange`
- [`app/Http/Controllers/AdminBookingController.php`](app/Http/Controllers/AdminBookingController.php) — `index`، `show`، `destroy`
- [`app/Http/Controllers/AdminCancellationController.php`](app/Http/Controllers/AdminCancellationController.php) — `index`، `create`، `store`، `show`، `edit`، `update`، `destroy`
- [`app/Http/Controllers/AdminDashboardController.php`](app/Http/Controllers/AdminDashboardController.php) — `wallet`، `index`
- [`app/Http/Controllers/AdminMessageController.php`](app/Http/Controllers/AdminMessageController.php) — `index`، `show`، `update`
- [`app/Http/Controllers/AdminPaymentController.php`](app/Http/Controllers/AdminPaymentController.php) — `index`، `show`
- [`app/Http/Controllers/AdminRefundsController.php`](app/Http/Controllers/AdminRefundsController.php) — `index`، `decide`
- [`app/Http/Controllers/AdminReviewController.php`](app/Http/Controllers/AdminReviewController.php) — `index`، `create`، `store`، `show`، `edit`، `update`، `destroy`
- [`app/Http/Controllers/AdminServiceApprovalController.php`](app/Http/Controllers/AdminServiceApprovalController.php) — `index`، `decide`
- [`app/Http/Controllers/AdminServicesController.php`](app/Http/Controllers/AdminServicesController.php) — `adminServices`، `image`، `storedImagePath`، `addServices`، `editServices`، `toggleServices`، `deleteServices`
- [`app/Http/Controllers/AdminUsersController.php`](app/Http/Controllers/AdminUsersController.php) — `index`، `toggleStatus`، `destroy`
- [`app/Http/Controllers/AdminVendorApplicationController.php`](app/Http/Controllers/AdminVendorApplicationController.php) — `index`، `approved`، `rejected`، `permit`
- [`app/Http/Controllers/AdminWalletController.php`](app/Http/Controllers/AdminWalletController.php) — `index`، `create`، `store`، `show`، `edit`، `update`، `destroy`
- [`app/Http/Controllers/Api/AdminAuthController.php`](app/Http/Controllers/Api/AdminAuthController.php) — `login`، `logout`
- [`app/Http/Controllers/Api/AdminDashboardController.php`](app/Http/Controllers/Api/AdminDashboardController.php) — `index`
- [`app/Http/Controllers/Auth/ConfirmPasswordController.php`](app/Http/Controllers/Auth/ConfirmPasswordController.php) — `__construct`
- [`app/Http/Controllers/Auth/ForgotPasswordController.php`](app/Http/Controllers/Auth/ForgotPasswordController.php) — `__construct`، `credentials`
- [`app/Http/Controllers/Auth/LoginController.php`](app/Http/Controllers/Auth/LoginController.php) — `redirectTo`، `authenticated`، `__construct`، `validateLogin`، `credentials`، `sendFailedLoginResponse`
- [`app/Http/Controllers/Auth/RegisterController.php`](app/Http/Controllers/Auth/RegisterController.php) — `__construct`، `register`، `validator`، `create`، `registered`
- [`app/Http/Controllers/Auth/ResetPasswordController.php`](app/Http/Controllers/Auth/ResetPasswordController.php) — `__construct`، `rules`، `credentials`
- [`app/Http/Controllers/Auth/VerificationController.php`](app/Http/Controllers/Auth/VerificationController.php) — `__construct`
- [`app/Http/Controllers/BookingCompletionController.php`](app/Http/Controllers/BookingCompletionController.php) — `confirm`، `review`، `object`
- [`app/Http/Controllers/BookingController.php`](app/Http/Controllers/BookingController.php) — `index`، `update`
- [`app/Http/Controllers/BookingProfileController.php`](app/Http/Controllers/BookingProfileController.php) — `store`، `cancel`
- [`app/Http/Controllers/CategoryController.php`](app/Http/Controllers/CategoryController.php) — `index`، `create`، `store`، `edit`، `update`، `destroy`، `validationMessages`
- [`app/Http/Controllers/Controller.php`](app/Http/Controllers/Controller.php)
- [`app/Http/Controllers/DashboardController.php`](app/Http/Controllers/DashboardController.php) — `index`
- [`app/Http/Controllers/FavoriteController.php`](app/Http/Controllers/FavoriteController.php) — `toggle`
- [`app/Http/Controllers/HomeController.php`](app/Http/Controllers/HomeController.php) — `__invoke`، `categories`، `category`، `vendors`، `visibleCategories`، `visibleVendors`
- [`app/Http/Controllers/MaintenanceAssistantController.php`](app/Http/Controllers/MaintenanceAssistantController.php) — `__invoke`، `matchingCategories`، `classify`، `suggestion`، `outsideScope`
- [`app/Http/Controllers/MarketController.php`](app/Http/Controllers/MarketController.php) — `index`، `show`، `availableDays`
- [`app/Http/Controllers/NotificationController.php`](app/Http/Controllers/NotificationController.php) — `index`، `read`
- [`app/Http/Controllers/PaymentController.php`](app/Http/Controllers/PaymentController.php) — `store`، `checkout`، `invoice`
- [`app/Http/Controllers/PortfolioController.php`](app/Http/Controllers/PortfolioController.php) — `show`، `image`، `store`، `update`، `save`، `destroy`، `destroyImage`، `authorizeOwner`، `authorizeView`
- [`app/Http/Controllers/ProfileController.php`](app/Http/Controllers/ProfileController.php) — `reviews`، `messages`، `sendMessage`، `dashboard`، `bookings`، `favorites`، `notifications`، `settings`، `updateSettings`، `customer`
- [`app/Http/Controllers/TableTestController.php`](app/Http/Controllers/TableTestController.php)
- [`app/Http/Controllers/UserPublicProfileController.php`](app/Http/Controllers/UserPublicProfileController.php) — `show`
- [`app/Http/Controllers/VendorApplicationController.php`](app/Http/Controllers/VendorApplicationController.php) — `show`، `store`
- [`app/Http/Controllers/VendorAvailabilityController.php`](app/Http/Controllers/VendorAvailabilityController.php) — `index`، `store`، `update`، `destroy`، `validatedWindow`، `assertNoOverlap`، `hasUpcomingBookings`
- [`app/Http/Controllers/VendorPagesController.php`](app/Http/Controllers/VendorPagesController.php) — `profile`، `dashboard`، `serviceReviews`، `serviceImage`، `authorizeOffer`، `visibleVendors`، `visibleOffers`
- [`app/Http/Controllers/VendorPublicController.php`](app/Http/Controllers/VendorPublicController.php) — `show`
- [`app/Http/Controllers/VendorReviewController.php`](app/Http/Controllers/VendorReviewController.php) — `index`، `store`، `update`، `destroy`، `activeReview`
- [`app/Http/Controllers/VendorServiceRequestController.php`](app/Http/Controllers/VendorServiceRequestController.php) — `store`، `suggest`، `index`، `update`، `withOfferImage`، `destroy`، `decide`
- [`app/Http/Controllers/VendorWalletController.php`](app/Http/Controllers/VendorWalletController.php) — `__invoke`
- [`app/Http/Controllers/YController.php`](app/Http/Controllers/YController.php)
- [`app/Http/Middleware/ActiveAdmin.php`](app/Http/Middleware/ActiveAdmin.php) — `handle`
- [`app/Http/Middleware/ActiveVendor.php`](app/Http/Middleware/ActiveVendor.php) — `handle`
- [`app/Models/Booking.php`](app/Models/Booking.php) — `scopeBlockingSlot`، `customer`، `acceptsCompletionConfirmation`، `scheduledStart`، `canBeCancelled`، `vendorService`، `availability`، `cancelledByUser`، `payment`، `walletTransactions`، `refundRequest`، `reviews`
- [`app/Models/Category.php`](app/Models/Category.php) — `services`
- [`app/Models/ContactMessage.php`](app/Models/ContactMessage.php) — `sender`
- [`app/Models/Favorite.php`](app/Models/Favorite.php) — `customer`، `vendorService`
- [`app/Models/Notification.php`](app/Models/Notification.php) — `user`
- [`app/Models/Payment.php`](app/Models/Payment.php) — `booking`
- [`app/Models/PortfolioImage.php`](app/Models/PortfolioImage.php) — `project`، `displayUrl`
- [`app/Models/PortfolioProject.php`](app/Models/PortfolioProject.php) — `vendor`، `images`، `coverImage`
- [`app/Models/RecurringAvailability.php`](app/Models/RecurringAvailability.php) — `vendor`، `bookings`
- [`app/Models/RefundRequest.php`](app/Models/RefundRequest.php) — `booking`، `customer`
- [`app/Models/Review.php`](app/Models/Review.php) — `booking`، `reviewer`، `reviewedUser`
- [`app/Models/Service.php`](app/Models/Service.php) — `category`، `vendorServices`، `suggestedBy`
- [`app/Models/Table_Test.php`](app/Models/Table_Test.php) — `newFactory`
- [`app/Models/User.php`](app/Models/User.php) — `casts`، `vendorProfile`، `vendorServices`، `recurringAvailabilities`، `portfolioProjects`، `bookingsAsCustomer`، `cancelledBookings`، `walletTransactions`، `refundRequests`، `reviewsGiven`، `reviewsReceived`، `favorites`، `notifications`، `sentContactMessages`
- [`app/Models/VendorProfile.php`](app/Models/VendorProfile.php) — `user`
- [`app/Models/VendorService.php`](app/Models/VendorService.php) — `scopePubliclyVisible`، `vendor`، `imageUrl`، `service`، `bookings`، `favorites`
- [`app/Models/WalletTransaction.php`](app/Models/WalletTransaction.php) — `vendor`، `booking`
- [`app/Models/YTable.php`](app/Models/YTable.php)
- [`app/Observers/VendorFinancialObserver.php`](app/Observers/VendorFinancialObserver.php) — `created`، `updated`، `notify`
- [`app/Providers/AppServiceProvider.php`](app/Providers/AppServiceProvider.php) — `register`، `boot`
- [`app/Rules/StrongPassword.php`](app/Rules/StrongPassword.php) — `validate`
- [`app/Services/AccountHistory.php`](app/Services/AccountHistory.php) — `forTab`، `filterDates`
- [`app/Services/AccountPhoto.php`](app/Services/AccountPhoto.php) — `url`، `storedFile`، `isManaged`، `normalize`
- [`app/Services/VendorOfferImage.php`](app/Services/VendorOfferImage.php) — `resolve`، `fallback`
- [`app/Services/VendorSettlement.php`](app/Services/VendorSettlement.php) — `completed`، `statusQuery`، `release`

### bootstrap

- [`bootstrap/app.php`](bootstrap/app.php)
- [`bootstrap/providers.php`](bootstrap/providers.php)

### routes

- [`routes/api.php`](routes/api.php)
- [`routes/console.php`](routes/console.php)
- [`routes/web.php`](routes/web.php)

### config

- [`config/app.php`](config/app.php)
- [`config/auth.php`](config/auth.php)
- [`config/cache.php`](config/cache.php)
- [`config/database.php`](config/database.php)
- [`config/filesystems.php`](config/filesystems.php)
- [`config/logging.php`](config/logging.php)
- [`config/mail.php`](config/mail.php)
- [`config/queue.php`](config/queue.php)
- [`config/sanctum.php`](config/sanctum.php)
- [`config/services.php`](config/services.php)
- [`config/session.php`](config/session.php)

### database

- [`database/demo-images/CREDITS.md`](database/demo-images/CREDITS.md)
- [`database/factories/BookingFactory.php`](database/factories/BookingFactory.php) — `definition`، `pending`، `paid`، `offer`
- [`database/factories/CategoryFactory.php`](database/factories/CategoryFactory.php) — `definition`
- [`database/factories/ContactMessageFactory.php`](database/factories/ContactMessageFactory.php) — `definition`
- [`database/factories/FavoriteFactory.php`](database/factories/FavoriteFactory.php) — `definition`
- [`database/factories/NotificationFactory.php`](database/factories/NotificationFactory.php) — `definition`
- [`database/factories/PaymentFactory.php`](database/factories/PaymentFactory.php) — `definition`
- [`database/factories/PortfolioImageFactory.php`](database/factories/PortfolioImageFactory.php) — `definition`
- [`database/factories/PortfolioProjectFactory.php`](database/factories/PortfolioProjectFactory.php) — `definition`
- [`database/factories/RecurringAvailabilityFactory.php`](database/factories/RecurringAvailabilityFactory.php) — `definition`
- [`database/factories/RefundRequestFactory.php`](database/factories/RefundRequestFactory.php) — `definition`
- [`database/factories/ReviewFactory.php`](database/factories/ReviewFactory.php) — `definition`، `fromVendor`
- [`database/factories/ServiceFactory.php`](database/factories/ServiceFactory.php) — `definition`
- [`database/factories/TableTestFactory.php`](database/factories/TableTestFactory.php) — `definition`
- [`database/factories/UserFactory.php`](database/factories/UserFactory.php) — `definition`، `vendor`، `admin`
- [`database/factories/VendorProfileFactory.php`](database/factories/VendorProfileFactory.php) — `definition`، `pending`
- [`database/factories/VendorServiceFactory.php`](database/factories/VendorServiceFactory.php) — `definition`
- [`database/factories/WalletTransactionFactory.php`](database/factories/WalletTransactionFactory.php) — `definition`
- [`database/factories/YFactory.php`](database/factories/YFactory.php) — `definition`
- [`database/migrations/0001_01_01_000000_create_users_table.php`](database/migrations/0001_01_01_000000_create_users_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234002_create_vendor_profiles_table.php`](database/migrations/2026_11_18_234002_create_vendor_profiles_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234003_create_categories_table.php`](database/migrations/2026_11_18_234003_create_categories_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234004_create_services_table.php`](database/migrations/2026_11_18_234004_create_services_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234005_create_vendor_services_table.php`](database/migrations/2026_11_18_234005_create_vendor_services_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234006_create_recurring_availability_table.php`](database/migrations/2026_11_18_234006_create_recurring_availability_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234007_create_portfolio_projects_table.php`](database/migrations/2026_11_18_234007_create_portfolio_projects_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234008_create_portfolio_images_table.php`](database/migrations/2026_11_18_234008_create_portfolio_images_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234009_create_bookings_table.php`](database/migrations/2026_11_18_234009_create_bookings_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234010_create_payments_table.php`](database/migrations/2026_11_18_234010_create_payments_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234011_create_wallet_transactions_table.php`](database/migrations/2026_11_18_234011_create_wallet_transactions_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234012_create_refund_requests_table.php`](database/migrations/2026_11_18_234012_create_refund_requests_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234013_create_reviews_table.php`](database/migrations/2026_11_18_234013_create_reviews_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234014_create_favorites_table.php`](database/migrations/2026_11_18_234014_create_favorites_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234015_create_notifications_table.php`](database/migrations/2026_11_18_234015_create_notifications_table.php) — `up`، `down`
- [`database/migrations/2026_11_18_234016_create_contact_messages_table.php`](database/migrations/2026_11_18_234016_create_contact_messages_table.php) — `up`، `down`
- [`database/migrations/2026_11_19_000000_add_laravel_ui_auth_storage.php`](database/migrations/2026_11_19_000000_add_laravel_ui_auth_storage.php) — `up`، `down`
- [`database/migrations/2026_11_19_170737_create_cache_table.php`](database/migrations/2026_11_19_170737_create_cache_table.php) — `up`، `down`
- [`database/migrations/2026_11_19_210801_change_status_to_enum_in_bookings_table.php`](database/migrations/2026_11_19_210801_change_status_to_enum_in_bookings_table.php) — `up`، `down`
- [`database/migrations/2026_11_20_000000_add_portfolio_and_vendor_offer_details.php`](database/migrations/2026_11_20_000000_add_portfolio_and_vendor_offer_details.php) — `up`، `down`
- [`database/migrations/2026_11_20_000001_add_active_flags_to_editable_vendor_records.php`](database/migrations/2026_11_20_000001_add_active_flags_to_editable_vendor_records.php) — `up`، `down`
- [`database/migrations/2026_11_20_000001_apply_approved_column_types.php`](database/migrations/2026_11_20_000001_apply_approved_column_types.php) — `up`، `down`
- [`database/migrations/2026_11_20_152028_update_user_foreign_keys_for_hard_delete.php`](database/migrations/2026_11_20_152028_update_user_foreign_keys_for_hard_delete.php) — `up`، `down`
- [`database/migrations/2026_11_20_152406_update_foreign_keys_for_hard_delete.php`](database/migrations/2026_11_20_152406_update_foreign_keys_for_hard_delete.php) — `up`، `down`
- [`database/migrations/2026_11_20_235900_add_deleted_at_to_bookings_table.php`](database/migrations/2026_11_20_235900_add_deleted_at_to_bookings_table.php) — `up`، `down`
- [`database/migrations/2026_11_21_031148_create_sessions_table.php`](database/migrations/2026_11_21_031148_create_sessions_table.php) — `up`، `down`
- [`database/migrations/2026_11_21_114940_add_is_active_to_services_table.php`](database/migrations/2026_11_21_114940_add_is_active_to_services_table.php) — `up`، `down`
- [`database/migrations/2026_11_21_123520_add_suggested_by_to_services_table.php`](database/migrations/2026_11_21_123520_add_suggested_by_to_services_table.php) — `up`، `down`
- [`database/migrations/2026_11_22_000001_add_completion_confirmations_to_bookings.php`](database/migrations/2026_11_22_000001_add_completion_confirmations_to_bookings.php) — `up`، `down`
- [`database/migrations/2026_11_22_000002_add_cancellation_amounts_to_bookings.php`](database/migrations/2026_11_22_000002_add_cancellation_amounts_to_bookings.php) — `up`، `down`
- [`database/migrations/2026_11_23_103440_create_y_s_table.php`](database/migrations/2026_11_23_103440_create_y_s_table.php) — `up`، `down`
- [`database/migrations/2026_11_23_104351_create_table__tests_table.php`](database/migrations/2026_11_23_104351_create_table__tests_table.php) — `up`، `down`
- [`database/migrations/2026_11_25_092247_create_personal_access_tokens_table.php`](database/migrations/2026_11_25_092247_create_personal_access_tokens_table.php) — `up`، `down`
- [`database/seeders/DatabaseSeeder.php`](database/seeders/DatabaseSeeder.php) — `run`
- [`database/seeders/PresentationSeeder.php`](database/seeders/PresentationSeeder.php) — `run`، `copyImages`، `seedCatalog`، `seedCustomers`، `seedBookings`، `book`، `seedMessages`، `offer`، `addWorkDays`، `fillImage`، `fillPhoto`، `phone`
- [`database/seeders/ServiceHubDemoSeeder.php`](database/seeders/ServiceHubDemoSeeder.php) — `run`، `seedServiceSuggestions`، `seedBookingActivity`، `seedExtraServiceReviews`، `renameOldDemoReviewers`، `seed`
- [`database/seeders/TableTestSeeder.php`](database/seeders/TableTestSeeder.php) — `run`
- [`database/seeders/YSeeder.php`](database/seeders/YSeeder.php) — `run`
- [`database/servicehub-bulk-demo.sql`](database/servicehub-bulk-demo.sql)
- [`database/servicehub-demo.sql`](database/servicehub-demo.sql)
- [`database/servicehub-month-and-week-demo.sql`](database/servicehub-month-and-week-demo.sql)

### resources

- [`resources/css/app.css`](resources/css/app.css)
- [`resources/js/app.js`](resources/js/app.js)
- [`resources/js/bootstrap.js`](resources/js/bootstrap.js)
- [`resources/sass/_variables.scss`](resources/sass/_variables.scss)
- [`resources/sass/app.scss`](resources/sass/app.scss)
- [`resources/views/auth/login.blade.php`](resources/views/auth/login.blade.php)
- [`resources/views/auth/passwords/confirm.blade.php`](resources/views/auth/passwords/confirm.blade.php)
- [`resources/views/auth/passwords/email.blade.php`](resources/views/auth/passwords/email.blade.php)
- [`resources/views/auth/passwords/reset.blade.php`](resources/views/auth/passwords/reset.blade.php)
- [`resources/views/auth/register.blade.php`](resources/views/auth/register.blade.php)
- [`resources/views/auth/verify.blade.php`](resources/views/auth/verify.blade.php)
- [`resources/views/components/account-details-editor.blade.php`](resources/views/components/account-details-editor.blade.php)
- [`resources/views/components/account-history-details.blade.php`](resources/views/components/account-history-details.blade.php)
- [`resources/views/components/account-history.blade.php`](resources/views/components/account-history.blade.php)
- [`resources/views/components/account-picture-editor.blade.php`](resources/views/components/account-picture-editor.blade.php)
- [`resources/views/components/account-picture.blade.php`](resources/views/components/account-picture.blade.php)
- [`resources/views/components/account-tabs.blade.php`](resources/views/components/account-tabs.blade.php)
- [`resources/views/components/archive-button.blade.php`](resources/views/components/archive-button.blade.php)
- [`resources/views/components/archive-dialog.blade.php`](resources/views/components/archive-dialog.blade.php)
- [`resources/views/components/booking-cancellation-action.blade.php`](resources/views/components/booking-cancellation-action.blade.php)
- [`resources/views/components/booking-charts-script.blade.php`](resources/views/components/booking-charts-script.blade.php)
- [`resources/views/components/booking-completion-actions.blade.php`](resources/views/components/booking-completion-actions.blade.php)
- [`resources/views/components/booking-filters.blade.php`](resources/views/components/booking-filters.blade.php)
- [`resources/views/components/booking-follow-up-dialogs.blade.php`](resources/views/components/booking-follow-up-dialogs.blade.php)
- [`resources/views/components/category-card.blade.php`](resources/views/components/category-card.blade.php)
- [`resources/views/components/dialog-date-time.blade.php`](resources/views/components/dialog-date-time.blade.php)
- [`resources/views/components/directory-photo.blade.php`](resources/views/components/directory-photo.blade.php)
- [`resources/views/components/file-picker.blade.php`](resources/views/components/file-picker.blade.php)
- [`resources/views/components/footer.blade.php`](resources/views/components/footer.blade.php)
- [`resources/views/components/home-faq.blade.php`](resources/views/components/home-faq.blade.php)
- [`resources/views/components/inline-choice.blade.php`](resources/views/components/inline-choice.blade.php)
- [`resources/views/components/mobile-nav-admin.blade.php`](resources/views/components/mobile-nav-admin.blade.php)
- [`resources/views/components/mobile-nav-customer.blade.php`](resources/views/components/mobile-nav-customer.blade.php)
- [`resources/views/components/mobile-nav-public.blade.php`](resources/views/components/mobile-nav-public.blade.php)
- [`resources/views/components/mobile-nav-vendor.blade.php`](resources/views/components/mobile-nav-vendor.blade.php)
- [`resources/views/components/navbar.blade.php`](resources/views/components/navbar.blade.php)
- [`resources/views/components/pager.blade.php`](resources/views/components/pager.blade.php)
- [`resources/views/components/public-stylesheet.blade.php`](resources/views/components/public-stylesheet.blade.php)
- [`resources/views/components/sidebars/admin.blade.php`](resources/views/components/sidebars/admin.blade.php)
- [`resources/views/components/sidebars/customer.blade.php`](resources/views/components/sidebars/customer.blade.php)
- [`resources/views/components/sidebars/vendor.blade.php`](resources/views/components/sidebars/vendor.blade.php)
- [`resources/views/components/vendor-card.blade.php`](resources/views/components/vendor-card.blade.php)
- [`resources/views/errors/403.blade.php`](resources/views/errors/403.blade.php)
- [`resources/views/errors/404.blade.php`](resources/views/errors/404.blade.php)
- [`resources/views/errors/layout.blade.php`](resources/views/errors/layout.blade.php)
- [`resources/views/layouts/app.blade.php`](resources/views/layouts/app.blade.php)
- [`resources/views/layouts/auth.blade.php`](resources/views/layouts/auth.blade.php)
- [`resources/views/pages/admin/admin-applications.blade.php`](resources/views/pages/admin/admin-applications.blade.php)
- [`resources/views/pages/admin/admin-approvals.blade.php`](resources/views/pages/admin/admin-approvals.blade.php)
- [`resources/views/pages/admin/admin-bookings-show.blade.php`](resources/views/pages/admin/admin-bookings-show.blade.php)
- [`resources/views/pages/admin/admin-bookings.blade.php`](resources/views/pages/admin/admin-bookings.blade.php)
- [`resources/views/pages/admin/admin-cancellations.blade.php`](resources/views/pages/admin/admin-cancellations.blade.php)
- [`resources/views/pages/admin/admin-categories-create.blade.php`](resources/views/pages/admin/admin-categories-create.blade.php)
- [`resources/views/pages/admin/admin-categories-edit.blade.php`](resources/views/pages/admin/admin-categories-edit.blade.php)
- [`resources/views/pages/admin/admin-categories.blade.php`](resources/views/pages/admin/admin-categories.blade.php)
- [`resources/views/pages/admin/admin-dashboard.blade.php`](resources/views/pages/admin/admin-dashboard.blade.php)
- [`resources/views/pages/admin/admin-messages-show.blade.php`](resources/views/pages/admin/admin-messages-show.blade.php)
- [`resources/views/pages/admin/admin-messages.blade.php`](resources/views/pages/admin/admin-messages.blade.php)
- [`resources/views/pages/admin/admin-payments-show.blade.php`](resources/views/pages/admin/admin-payments-show.blade.php)
- [`resources/views/pages/admin/admin-payments.blade.php`](resources/views/pages/admin/admin-payments.blade.php)
- [`resources/views/pages/admin/admin-refunds.blade.php`](resources/views/pages/admin/admin-refunds.blade.php)
- [`resources/views/pages/admin/admin-reviews.blade.php`](resources/views/pages/admin/admin-reviews.blade.php)
- [`resources/views/pages/admin/admin-services.blade.php`](resources/views/pages/admin/admin-services.blade.php)
- [`resources/views/pages/admin/admin-users.blade.php`](resources/views/pages/admin/admin-users.blade.php)
- [`resources/views/pages/admin/admin-wallet.blade.php`](resources/views/pages/admin/admin-wallet.blade.php)
- [`resources/views/pages/admin/partials/category-form-fields.blade.php`](resources/views/pages/admin/partials/category-form-fields.blade.php)
- [`resources/views/pages/admin/partials/flash-messages.blade.php`](resources/views/pages/admin/partials/flash-messages.blade.php)
- [`resources/views/pages/admin/vendor-service-requests.blade.php`](resources/views/pages/admin/vendor-service-requests.blade.php)
- [`resources/views/pages/customer/customer-bookings.blade.php`](resources/views/pages/customer/customer-bookings.blade.php)
- [`resources/views/pages/customer/customer-dashboard.blade.php`](resources/views/pages/customer/customer-dashboard.blade.php)
- [`resources/views/pages/customer/customer-favorites.blade.php`](resources/views/pages/customer/customer-favorites.blade.php)
- [`resources/views/pages/customer/customer-messages.blade.php`](resources/views/pages/customer/customer-messages.blade.php)
- [`resources/views/pages/customer/customer-notifications.blade.php`](resources/views/pages/customer/customer-notifications.blade.php)
- [`resources/views/pages/customer/customer-refund.blade.php`](resources/views/pages/customer/customer-refund.blade.php)
- [`resources/views/pages/customer/customer-reviews.blade.php`](resources/views/pages/customer/customer-reviews.blade.php)
- [`resources/views/pages/customer/customer-settings.blade.php`](resources/views/pages/customer/customer-settings.blade.php)
- [`resources/views/pages/customer/menu.blade.php`](resources/views/pages/customer/menu.blade.php)
- [`resources/views/pages/partials/market-assets.blade.php`](resources/views/pages/partials/market-assets.blade.php)
- [`resources/views/pages/public/about.blade.php`](resources/views/pages/public/about.blade.php)
- [`resources/views/pages/public/account.blade.php`](resources/views/pages/public/account.blade.php)
- [`resources/views/pages/public/become-vendor.blade.php`](resources/views/pages/public/become-vendor.blade.php)
- [`resources/views/pages/public/booking-details.blade.php`](resources/views/pages/public/booking-details.blade.php)
- [`resources/views/pages/public/booking.blade.php`](resources/views/pages/public/booking.blade.php)
- [`resources/views/pages/public/category.blade.php`](resources/views/pages/public/category.blade.php)
- [`resources/views/pages/public/checkout.blade.php`](resources/views/pages/public/checkout.blade.php)
- [`resources/views/pages/public/contact.blade.php`](resources/views/pages/public/contact.blade.php)
- [`resources/views/pages/public/directory.blade.php`](resources/views/pages/public/directory.blade.php)
- [`resources/views/pages/public/faq.blade.php`](resources/views/pages/public/faq.blade.php)
- [`resources/views/pages/public/forgot-password.blade.php`](resources/views/pages/public/forgot-password.blade.php)
- [`resources/views/pages/public/how-it-works.blade.php`](resources/views/pages/public/how-it-works.blade.php)
- [`resources/views/pages/public/index.blade.php`](resources/views/pages/public/index.blade.php)
- [`resources/views/pages/public/notifications.blade.php`](resources/views/pages/public/notifications.blade.php)
- [`resources/views/pages/public/service-details.blade.php`](resources/views/pages/public/service-details.blade.php)
- [`resources/views/pages/public/services.blade.php`](resources/views/pages/public/services.blade.php)
- [`resources/views/pages/public/user-profile.blade.php`](resources/views/pages/public/user-profile.blade.php)
- [`resources/views/pages/public/vendors.blade.php`](resources/views/pages/public/vendors.blade.php)
- [`resources/views/pages/vendor/partials/offer-image-field.blade.php`](resources/views/pages/vendor/partials/offer-image-field.blade.php)
- [`resources/views/pages/vendor/partials/offer-management.blade.php`](resources/views/pages/vendor/partials/offer-management.blade.php)
- [`resources/views/pages/vendor/partials/project-image-fields.blade.php`](resources/views/pages/vendor/partials/project-image-fields.blade.php)
- [`resources/views/pages/vendor/partials/project-management.blade.php`](resources/views/pages/vendor/partials/project-management.blade.php)
- [`resources/views/pages/vendor/partials/service-offer-fields.blade.php`](resources/views/pages/vendor/partials/service-offer-fields.blade.php)
- [`resources/views/pages/vendor/partials/service-request-dialog.blade.php`](resources/views/pages/vendor/partials/service-request-dialog.blade.php)
- [`resources/views/pages/vendor/partials/service-suggestion-dialog.blade.php`](resources/views/pages/vendor/partials/service-suggestion-dialog.blade.php)
- [`resources/views/pages/vendor/portfolio-project.blade.php`](resources/views/pages/vendor/portfolio-project.blade.php)
- [`resources/views/pages/vendor/vendor-availability.blade.php`](resources/views/pages/vendor/vendor-availability.blade.php)
- [`resources/views/pages/vendor/vendor-bookings.blade.php`](resources/views/pages/vendor/vendor-bookings.blade.php)
- [`resources/views/pages/vendor/vendor-dashboard.blade.php`](resources/views/pages/vendor/vendor-dashboard.blade.php)
- [`resources/views/pages/vendor/vendor-portfolio-form.blade.php`](resources/views/pages/vendor/vendor-portfolio-form.blade.php)
- [`resources/views/pages/vendor/vendor-portfolio.blade.php`](resources/views/pages/vendor/vendor-portfolio.blade.php)
- [`resources/views/pages/vendor/vendor-profile.blade.php`](resources/views/pages/vendor/vendor-profile.blade.php)
- [`resources/views/pages/vendor/vendor-reviews.blade.php`](resources/views/pages/vendor/vendor-reviews.blade.php)
- [`resources/views/pages/vendor/vendor-service-form.blade.php`](resources/views/pages/vendor/vendor-service-form.blade.php)
- [`resources/views/pages/vendor/vendor-services.blade.php`](resources/views/pages/vendor/vendor-services.blade.php)
- [`resources/views/pages/vendor/vendor-settings.blade.php`](resources/views/pages/vendor/vendor-settings.blade.php)
- [`resources/views/pages/vendor/vendor-wallet.blade.php`](resources/views/pages/vendor/vendor-wallet.blade.php)
- [`resources/views/welcome.blade.php`](resources/views/welcome.blade.php)

### public/assets

- [`public/assets/admin_style.css`](public/assets/admin_style.css)
- [`public/assets/admin-management.css`](public/assets/admin-management.css)
- [`public/assets/app.js`](public/assets/app.js)
- [`public/assets/servicehub-pages.css`](public/assets/servicehub-pages.css)
- [`public/assets/servicehub-pages.js`](public/assets/servicehub-pages.js)
- [`public/assets/style.css`](public/assets/style.css)
- [`public/assets/theme.css`](public/assets/theme.css)
- [`public/assets/theme.js`](public/assets/theme.js)

### tests

- [`tests/Feature/AdminBookingManagementTest.php`](tests/Feature/AdminBookingManagementTest.php) — `test_an_active_admin_can_search_filter_paginate_and_view_bookings`، `test_an_admin_can_archive_bookings_at_any_stage_and_preserve_paid_history`، `test_admin_can_search_bookings_by_customer_name_and_sees_archive_message`، `test_guests_and_users_without_active_admin_access_cannot_manage_bookings`
- [`tests/Feature/AdminCategoryManagementTest.php`](tests/Feature/AdminCategoryManagementTest.php) — `test_admin_can_list_and_search_active_categories`، `test_admin_can_create_update_and_archive_a_category`، `test_category_validation_rejects_invalid_data`، `test_non_admins_cannot_manage_categories`، `fakeImage`
- [`tests/Feature/AdminMessageManagementTest.php`](tests/Feature/AdminMessageManagementTest.php) — `test_admin_can_list_search_and_filter_messages`، `test_admin_can_view_and_mark_a_message_as_reviewed`، `test_non_admins_cannot_access_messages`
- [`tests/Feature/AdminPaymentManagementTest.php`](tests/Feature/AdminPaymentManagementTest.php) — `test_admin_can_list_filter_and_search_payments`، `test_payment_of_archived_booking_stays_visible_with_details`، `test_non_admins_cannot_view_payments`
- [`tests/Feature/ExampleTest.php`](tests/Feature/ExampleTest.php) — `test_the_application_returns_a_successful_response`
- [`tests/Feature/RouteIntegrityTest.php`](tests/Feature/RouteIntegrityTest.php) — `test_missing_pages_and_forbidden_pages_keep_their_http_status`، `test_public_and_role_navigation_pages_render_with_demo_records`
- [`tests/Feature/SiteAuditRegressionTest.php`](tests/Feature/SiteAuditRegressionTest.php) — `test_admin_can_upload_replace_and_view_a_service_image`، `test_admin_catalog_displays_messages_after_toggling_and_deleting_a_service`، `test_admin_overview_links_open_their_destination_pages`، `test_admin_application_list_links_only_to_available_permits`، `test_vendor_availability_displays_saturday_first`، `test_admin_can_search_full_names_and_invalid_filters_return_validation_errors`، `test_customer_can_book_and_pay_and_other_customers_cannot_access_the_checkout`، `test_elapsed_booking_cannot_be_approved_or_paid`، `test_accepted_objection_records_refund_and_shows_the_vendor_share`، `test_booking_cancellations_record_refund_and_wallet_adjustments`، `test_admin_wallet_displays_archived_booking_transactions_and_rejects_other_roles`، `test_admin_can_permanently_delete_the_selected_vendor_with_a_suggested_service`، `test_admin_can_filter_join_requests_and_sees_pending_requests_first`، `test_admin_approval_promotes_customer_once`
- [`tests/Feature/UiAuthenticationTest.php`](tests/Feature/UiAuthenticationTest.php) — `test_admin_is_sent_to_the_dashboard_after_login`، `test_vendor_can_log_in_with_remember_me_and_log_out`، `test_suspended_account_cannot_log_in`، `test_registration_and_login_reject_passwords_missing_strength_requirements`، `test_registration_saves_an_optional_profile_image`، `test_password_reset_sends_notification_and_consumes_token`، `account`
- [`tests/Feature/VendorApplicationTest.php`](tests/Feature/VendorApplicationTest.php) — `test_guest_can_open_the_page_and_return_after_registering`، `test_customer_can_submit_once_and_permit_is_stored_privately`، `test_invalid_permit_and_ineligible_accounts_are_rejected`، `createAccount`
- [`tests/Feature/VendorWorkspaceTest.php`](tests/Feature/VendorWorkspaceTest.php) — `test_vendor_dashboard_chart_counts_only_own_bookings_and_nav_has_one_dashboard_link`، `test_visitors_switch_between_services_and_portfolio_without_seeing_private_tabs`، `test_demo_seed_has_at_least_fifteen_rows_per_business_table_and_can_be_rerun`، `test_vendor_can_replace_an_avatar_and_only_public_vendors_share_it`، `test_landing_previews_link_to_directories_and_category_details`، `test_wallet_releases_only_after_the_hold_without_an_open_complaint_and_notifies`، `test_only_the_assigned_vendor_can_rate_the_customer_once_after_completion`، `test_vendor_can_publish_a_project_with_multiple_images`، `test_vendor_can_replace_a_project_image_at_the_eight_image_limit`، `test_catalog_offer_publishes_directly_and_uses_only_its_linked_image`، `test_new_service_suggestion_requires_admin_decision_before_publication`، `test_vendor_can_upload_and_replace_one_image_per_offer_or_suggestion`، `test_vendor_can_edit_archive_and_rerate_the_same_booking`، `test_vendor_can_update_details_and_archive_owned_offers_and_portfolio_items`، `test_assistant_handles_missing_configuration_and_accepts_a_provider_response`، `test_assistant_asks_a_follow_up_and_only_recommends_live_catalog_services`، `test_assistant_keeps_earlier_details_across_multiple_follow_ups`، `test_assistant_understands_sofa_damage_and_asks_about_the_damaged_part`، `test_vendor_registration_creates_a_pending_profile_and_stores_the_permit_privately`، `test_active_accounts_can_request_another_vendors_service_and_see_their_own_history`
- [`tests/TestCase.php`](tests/TestCase.php)
- [`tests/Unit/ExampleTest.php`](tests/Unit/ExampleTest.php) — `test_that_true_is_true`

### ملفات التشغيل في الجذر

- [`artisan`](artisan) أوامر Laravel
- [`composer.json`](composer.json) حزم PHP وأوامر Composer
- [`package.json`](package.json) حزم وأوامر الواجهة
- [`vite.config.js`](vite.config.js) نقاط دخول بناء الواجهة
- [`phpunit.xml`](phpunit.xml) إعداد الاختبارات
- [`.env.example`](.env.example) نموذج مفاتيح الإعداد
- [`public/index.php`](public/index.php) نقطة دخول HTTP
- [`public/.htaccess`](public/.htaccess) قواعد التوجيه في Apache
