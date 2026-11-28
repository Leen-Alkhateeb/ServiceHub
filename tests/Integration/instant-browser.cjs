const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { chromium } = require(process.env.SERVICEHUB_PLAYWRIGHT_CORE || 'playwright-core');

const base = process.env.SERVICEHUB_BROWSER_URL || 'http://127.0.0.1:8794';
assert.match(base, /^http:\/\/127\.0\.0\.1:87\d\d$/);
const geolocation = { latitude: 31.95, longitude: 35.91, accuracy: 10 };
const browserOptions = { headless: true };
if (process.env.SERVICEHUB_CHROME) browserOptions.executablePath = process.env.SERVICEHUB_CHROME;

async function signIn(browser, role, options = {}) {
    const context = await browser.newContext({ ...options, baseURL: base });
    const page = await context.newPage();
    await page.goto('/login');
    await page.getByLabel('البريد الإلكتروني').fill(`${role}.phase4@example.test`);
    await page.getByLabel('كلمة المرور').fill('Browser-Phase4-9!');
    await page.getByRole('button', { name: 'دخول', exact: true }).click();
    await page.getByLabel('قائمة الحساب').waitFor();
    return { context, page };
}

const button = (page, name) => page.getByRole('button', { name, exact: true });

async function run() {
    const browser = await chromium.launch(browserOptions);
    try {
        const customer = await signIn(browser, 'customer', { geolocation, permissions: ['geolocation'] });
        const vendor = await signIn(browser, 'vendor', { geolocation, permissions: ['geolocation'] });
        const admin = await signIn(browser, 'admin');
        const errors = [];
        for (const { page } of [customer, vendor, admin]) page.on('pageerror', error => errors.push(error.message));
        const c = customer.page;
        const v = vendor.page;
        const a = admin.page;
        assert.equal((await c.goto('/admin/now')).status(), 403);
        await a.goto('/admin/now');
        await a.getByText(/الرصيد التجريبي المتاح/).waitFor();
        await v.goto('/now/vendor');
        await button(v, 'استخدم موقعي الحالي').click();
        await v.getByText('تم تحديد الموقع.', { exact: true }).waitFor();
        await button(v, 'تفعيل استقبال الطلبات').click();
        await button(v, 'إيقاف التوفّر ومشاركة الموقع').waitFor();

        await vendor.context.setOffline(true);
        await v.getByText(/تعذّر تحديث البيانات/).waitFor({ timeout: 12000 });
        await vendor.context.setOffline(false);
        await v.getByText(/تعذّر تحديث البيانات/).waitFor({ state: 'hidden', timeout: 12000 });
        console.log('Independent sessions, admin authorization, network recovery passed.');

        await c.goto('/now');
        await c.getByLabel('الخدمة', { exact: true }).selectOption({ label: 'فحص تجريبي للمرحلة الرابعة' });
        await c.getByLabel('وصف المشكلة').fill('مشكلة تجريبية في خدمة الصيانة');
        await c.getByLabel('العنوان').fill('عنوان تجريبي غير حقيقي');
        await button(c, 'استخدم موقعي الحالي').click();
        await c.getByText('تم تحديد الموقع.', { exact: true }).waitFor();
        await button(c, 'ابحث عن فني').click();
        await c.getByRole('heading', { name: /عروض الطلب/ }).waitFor();
        await v.getByRole('heading', { name: /فحص تجريبي للمرحلة الرابعة/ }).waitFor({ timeout: 15000 });
        assert.equal(await v.getByText('عنوان تجريبي غير حقيقي').count(), 0);
        await v.getByLabel('سعرك بالدينار الأردني').fill('100');
        await v.getByLabel('الوصول بالدقائق').fill('20');
        await v.getByLabel('مدة العمل بالدقائق').fill('30');
        await button(v, 'إرسال عرض').click();
        await button(c, 'اختيار العرض').click({ timeout: 15000 });
        await c.waitForURL(/\/now\/bookings\/\d+$/);
        await v.goto(c.url());
        await button(v, 'تأكيد المغادرة').click();
        await button(v, 'استخدم موقعي الحالي').click();
        await v.getByText('تم تحديد الموقع.', { exact: true }).waitFor();
        await button(v, 'تسجيل الوصول').click();
        await v.locator('strong bdi').waitFor();
        const code = await v.locator('strong bdi').innerText();
        assert.match(code, /^\d{6}$/);
        await c.getByLabel('الرمز من الفني').fill(code, { timeout: 15000 });
        await button(c, 'تحقق من الرمز').click();
        await button(v, 'بدء الخدمة').click({ timeout: 15000 });
        await v.getByLabel('السعر المقترح بالدينار').fill('110');
        await v.locator('#price-reason').fill('تعديل تجريبي للعمل');
        await button(v, 'طلب تعديل السعر').click();
        await button(c, 'قبول السعر').click({ timeout: 15000 });
        await v.getByText(/السعر المتفق عليه 110\.000/).waitFor({ timeout: 15000 });
        const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAEElEQVQokWNgGAWjYBTAAAADEAABC3uRhAAAAABJRU5ErkJggg==', 'base64');
        await v.locator('#now-after').setInputFiles({ name: 'test.png', mimeType: 'image/png', buffer: png });
        await v.getByLabel('وصف العمل المنجَز').fill('اكتملت الخدمة التجريبية');
        await button(v, 'تأكيد الإنجاز').click();
        await v.getByText('تم تنفيذ الخدمة', { exact: true }).waitFor().catch(async error => {
            throw new Error(`Completion did not succeed: ${await v.locator('main').innerText()}`, { cause: error });
        });
        await c.getByRole('link', { name: /تحميل صورة بعد العمل/ }).waitFor({ timeout: 15000 });
        await button(c, 'دفع تجريبي').click();
        await c.getByText('تم الدفع.', { exact: true }).waitFor();
        console.log('Location, matching, offer polling, acceptance, OTP, price, evidence and simulated payment passed.');

        await c.getByText('رفع حالة للإدارة', { exact: true }).click();
        await c.getByLabel('نوع الحالة').selectOption('suspected_fraud');
        await c.getByLabel('وصف الحالة').fill('بلاغ تجريبي لاختبار تعليق التسوية');
        await button(c, 'إرسال الحالة').click();
        await c.getByText(/اشتباه بالاحتيال.*قيد المراجعة/).waitFor();
        await a.goto(c.url());
        await a.getByLabel('سبب القرار').fill('بلاغ تجريبي دون دليل كافٍ');
        await button(a, 'رفض طلب المراجعة').click();
        await c.getByText(/اشتباه بالاحتيال.*مرفوض/).waitFor({ timeout: 15000 });
        const partial = process.env.SERVICEHUB_BROWSER_PARTIAL === '1';
        if (partial) {
            await c.getByText('رفع حالة للإدارة', { exact: true }).click();
            await c.getByLabel('نوع الحالة').selectOption('partial_service');
            await c.getByLabel('وصف الحالة').fill('تم تنفيذ جزء من العمل فقط');
            await button(c, 'إرسال الحالة').click();
            await a.reload();
            await a.getByLabel('قيمة العمل المعتمدة (د.أ)').fill('40');
            await a.getByLabel('سبب القرار', { exact: true }).fill('تمت مراجعة دليل الإنجاز الجزئي');
            await a.getByLabel('أوافق على تنفيذ القرار المالي بعد مراجعة الأدلة والحالات المرتبطة.').check();
            await button(a, 'اعتماد القرار المالي').click();
            await c.getByText(/قيمة العمل المعتمدة 40\.000.*الاسترداد 70\.000/).waitFor({ timeout: 15000 });
        }
        const payout = execFileSync('php', ['tests/Integration/instant-browser-fixtures.php', 'settle'], { encoding: 'utf8' }).trim();
        assert.equal(payout, partial ? '34.000' : '93.500');
        await a.goto('/admin/now');
        await a.getByText(partial ? /الرصيد التجريبي المتاح 6\.000/ : /الرصيد التجريبي المتاح 16\.500/).waitFor();
        console.log('Exceptional case and deadline-controlled settlement passed.');

        for (const width of [390, 768, 1280]) {
            for (const page of [c, v, a]) {
                await page.setViewportSize({ width, height: 850 });
                const fits = await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth);
                assert.ok(fits, `Horizontal overflow at ${width}: ${page.url()}`);
            }
        }
        assert.deepEqual(errors, []);
        console.log('Three interfaces passed overflow checks at 390, 768 and 1280 pixels; no page errors.');
    } finally {
        await browser.close();
    }
}

run().catch(error => { console.error(error); process.exitCode = 1; });
