@extends('layouts.app')
@section('title', 'الرئيسية | ServiceHub')
@section('page_id', 'index')
@section('content')
    @include('components.navbar')
    <main>
        <section class="hero">
            <div class="container"><span class="eyebrow">خدمات الصيانة بمكان واحد</span>
                <h1>عندك عطل؟ <span>صلّحها.</span></h1>
                <p>احكيلنا شو المشكلة، واكتشف الفنيين المناسبين، قارن الخدمات والتقييمات، واحجز الموعد المناسب بكل سهولة.
                </p>
                <div class="hero-actions"><a class="btn btn-yellow" href="/services">اكتشف الفني المناسب</a><a
                        class="btn btn-outline" href="/how-it-works">كيف تعمل المنصة؟</a></div>
            </div>
        </section>
        <div class="container">
            <div class="assistant-search">
                <h3>مش عارف أي فني بتحتاج؟ خلّينا نساعدك</h3>
                <p class="muted small">اكتب المشكلة بطريقتك، ورح نقترح القسم الأقرب. نموذج توضيحي؛ Gemini يحتاج تكامل خلفي.
                </p>
                <form id="ai-form"><input class="searchbox" id="ai-query"
                        placeholder="مثال: المكيف بالمكتب شغال بس مش ببرّد" required="" /><button class="btn btn-dark">جرّب
                        المساعد</button></form>
                <div id="ai-result"></div>
            </div>
        </div>
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h2>شو نوع الصيانة اللي بتحتاجها؟</h2>
                        <p class="muted">خدمات للمكاتب والمحلات والمنشآت والمنازل.</p>
                    </div>
                </div>
                <div class="grid"><a class="card" href="/services?cat=السباكة">
                        <div class="art blue">السباكة</div>
                        <div class="card-body">
                            <h3>السباكة</h3>
                            <p>تسريبات، تمديدات، سخانات وخزانات</p>
                        </div>
                    </a><a class="card" href="/services?cat=الكهرباء">
                        <div class="art gold">الكهرباء</div>
                        <div class="card-body">
                            <h3>الكهرباء</h3>
                            <p>لوحات توزيع، أعطال، إنارة وتمديدات</p>
                        </div>
                    </a><a class="card" href="/services?cat=صيانة المكيفات">
                        <div class="art">صيانة المكيفات</div>
                        <div class="card-body">
                            <h3>صيانة المكيفات</h3>
                            <p>تنظيف وفحص وتبريد وصيانة</p>
                        </div>
                    </a></div>
            </div>
        </section>
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h2>من وصف المشكلة لمتابعة الطلب</h2>
                        <p class="muted"></p>
                    </div>
                </div>
                <div class="grid"><a class="feature" href="/services">
                        <div class="feature-icon">✓</div>
                        <h3>١ · اوصف المشكلة أو اختار الخدمة</h3>
                        <p>ابحث عن نوع الصيانة المناسب.</p>
                    </a><a class="feature" href="/vendors">
                        <div class="feature-icon">✓</div>
                        <h3>٢ · قارن الفنيين والخدمات</h3>
                        <p>شوف الأسعار والتقييمات والأعمال السابقة.</p>
                    </a><a class="feature" href="/booking">
                        <div class="feature-icon">✓</div>
                        <h3>٣ · احجز الموعد المناسب</h3>
                        <p>اختار نافذة من جدول الفني الأسبوعي.</p>
                    </a><a class="feature" href="/customer-bookings">
                        <div class="feature-icon">✓</div>
                        <h3>٤ · تابع طلبك</h3>
                        <p>الموافقة، الدفع، الإنجاز والتقييم.</p>
                    </a></div>
            </div>
        </section>
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h2>فنيون على المنصة</h2>
                        <p class="muted"></p>
                    </div>
                </div>
                <div class="grid four" id="home-cards"></div>
            </div>
        </section>
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h2>ترتيب الصيانة صار أوضح وأسهل</h2>
                        <p class="muted"></p>
                    </div>
                </div>
                <div class="grid"><a class="feature" href="/booking">
                        <div class="feature-icon">✓</div>
                        <h3>حجز منظّم</h3>
                        <p>مواعيد من جدول الفني وحالة طلب واضحة.</p>
                    </a><a class="feature" href="/vendors">
                        <div class="feature-icon">✓</div>
                        <h3>مقارنة بين الفنيين</h3>
                        <p>أسعار، أعمال سابقة وتقييمات.</p>
                    </a><a class="feature" href="/services">
                        <div class="feature-icon">✓</div>
                        <h3>معلومات خدمة واضحة</h3>
                        <p>وصف وسعر كل خدمة من الفني نفسه.</p>
                    </a></div>
            </div>
        </section>
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h2>عندك خبرة بالصيانة؟</h2>
                        <p class="muted"></p>
                    </div>
                </div>
                <div class="panel">
                    <h2>انضم لفريق الفنيين</h2>
                    <p>قدّم طلبك، ارفع تصريح العمل، واعرض خدماتك وأسعارك ومواعيدك بعد موافقة الإدارة.</p><a
                        class="btn btn-yellow" href="/become-vendor">قدّم طلب الانضمام</a>
                </div>
            </div>
        </section>
    </main>@include('components.footer')@include('components.mobile-nav-public')
@endsection