# محرك الصلاحيات الوظيفية

بيجاوب على سؤال "أنهي شاشات/أفعال يقدر المستخدم ده يفتحها"، بغض النظر عن أنهي صفوف بيانات هيشوفها لما يدخل (ده موضوع [data-scope.md](data-scope)). مبني بالكامل على `spatie/laravel-permission` (كانت مثبّتة وغير مستخدمة قبل المحرك ده) — Spatie لسه مصدر الحقيقة وقت القراءة، فكل `$user->can()`، `@can`، وفحوصات الـ policy بتشتغل من غير أي تعديل.

## ليه توليد مش كتابة يدوية

محركات Dynamic Table / Dynamic Record View / شجرة التنقّل كانت أصلاً بتنادي `auth()->user()->can($application->permission_name)` في كل مكان — الـ hook ده كان موجود قبل المحرك ده وكان بس مش متغذّي بأسماء صلاحيات حقيقية. بناء المحرك ده كان أساسًا **الرد على السؤال اللي الواجهة كانت بتسأله أصلاً**، مش إضافة طبقة جديدة.

كل صف `modules`/`sub_modules`/`applications` أصلاً معاه `code` هرمي (`fin-gl-jou`، متولّد من `RecordCodeBuilder` — شوف `.ai/rules/code-field-hierarchy.md`). أسماء الصلاحيات مشتقة مباشرة من الكود ده، فمفيش أبدًا نظام تسمية تاني نحافظ عليه يدويًا.

## `php artisan permissions:sync`

يتشغّل بعد ما تتزرع أي صف جديد `Module`/`SubModule`/`Application`.

- **الموديولات والـ submodules** بياخدوا فعلين بس: `{code}.view`, `{code}.update`.
- **الـ Applications** بياخدوا ٦ أفعال قياسية — `view`, `create`, `update`, `delete`, `export`, `print` — زائد أي `custom_actions` (عمود `json` على `applications`) الـ Application بيعلنها، زي `post` لتطبيق قيود اليومية.
- بيحط `permission_name = "{code}.view"` على كل صف (بيستخدمه `RecordReferenceAccess::applicationAccessible()` و`NavigationTreeService` لحجب الصفحة وفلترة القائمة الجانبية على المستويات التلاتة).
- Idempotent — آمن تعيد تشغيله؛ بيعمل بس الصلاحيات اللي مش موجودة، وبينادي `PermissionRegistrar::forgetCachedPermissions()` مرة واحدة في الآخر.

```php
// إعلان فعل خاص على Application:
Application::create([..., 'custom_actions' => ['post']]);
// permissions:sync بعدها هيعمل fin-gl-jou.post جنب الستة أفعال القياسية.
```

## جداول المنح

٤ جداول، كلهم تحت `Modules/HR/Database/Migrations/`. مجموعة صلاحيات المستخدم الفعلية هي **اتحاد** كل الصفوف المنطبقة عبر الأربعة:

| الجدول | بيمنح لـ | ملاحظات |
|---|---|---|
| `department_permissions` | كل حد في الإدارة | منح مباشرة بس — **مش موروثة** لتحت في شجرة الإدارات (إدارة فرعية مش بتاخد صلاحيات الأم تلقائيًا؛ شوف [data-scope.md](data-scope#departments-are-a-shared-catalog)) |
| `job_title_permissions` | كل حد شايل اللقب، أي درجة | الأساس اللي كل درجات اللقب بتاخده |
| `job_title_grade_permissions` | حاملي اللقب **من درجة معيّنة فما فوق** | المقارنة دايمًا جوه نفس `job_title_id`؛ senior في لقب معيّن مايتقارنش بـ senior في لقب تاني. مثلاً "محاسب عام" @ senior ياخد `fin-gl-jou.post`؛ junior لأ |
| `job_title_grade_roles` | دور Spatie مسمّى (زي `finance_manager`) لحاملي لقب، اختياريًا محدد بدرجة (`job_grade_id` فاضي = كل الدرجات) | للأدوار اللي الشركة بتسميها صراحة ("مدير مالي")، مش مجرد صلاحية خام |

## `EmployeePermissionSynchronizer`

الكاتب **الوحيد** لـ `model_has_permissions` / `model_has_roles`. بيشتغل تلقائيًا مع كل `Employee::saved()` — تعيين، نقل، ترقية، إنهاء خدمة كلهم بيعملوا إعادة مزامنة بدون شرط (مش بس على الحقول اللي اتغيّرت، لأن جداول المنح نفسها ممكن تتغيّر بمعزل عن سطر الموظف).

```php
app(EmployeePermissionSynchronizer::class)->sync($employee);
// أو، للفحص بلا كتابة:
$target = app(EmployeePermissionSynchronizer::class)->resolve($employee); // ['permissions' => [...], 'roles' => [...]]
```

موظف متوقف/منتهي الخدمة (`status !== 'active'`) بتتصفّر صلاحياته وأدواره فورًا — مش لحد أول دخول جاي.

كمان بيلغي كاش شجرة التنقّل مع كل مزامنة (`NavigationTreeService::invalidateCache()`) — مفتاح الكاش ده بيعتمد على hash لأسماء **الأدوار** بس في Spatie، فمنحة صلاحية بس (بلا دور) كانت هتسيب القائمة الجانبية على نسخة قديمة لحد ما كتابة غير مرتبطة (على Module/SubModule/Application) تحصل وتزود العدّاد بالصدفة. ده باج حقيقي اتمسك بـ [اختبار مخصص](testing#cache-invalidation)، مش افتراض نظري.

## تصحيح الانحراف

بما إن المزامن هو الكاتب الوحيد، أي انحراف معناه إن استدعاء مزامنة اتنسى — تعديل مباشر في جدول منح، أو job في queue فشل نص الطريق. `hr:permissions:reconcile` هو الحارس:

```bash
php artisan hr:permissions:reconcile              # يعيد الحساب، يصلّح، ويقرّر
php artisan hr:permissions:reconcile --dry-run     # يقرّر بس، بلا كتابة
```

بيمشي على كل `Employee` نشط ومسجّل دخول (`chunkById(200, ...)`), بيقارن حالة Spatie الحالية بالي `EmployeePermissionSynchronizer::resolve()` بيحسبه من الصفر، وبيقرّر (وممكن يصلّح) أي فرق. مصمم يتشغّل مجدول، مش بس عند الحاجة.

## مثال كامل: "محاسب عام"

المثال اللي شكّل التصميم ده، ودلوقتي مدعوم بمجموعة اختبارات ناجحة:

1. `department_permissions`: إدارة المالية → `fin-gl-jou.view` (كل حد في المالية يقدر يشوف اليومية، بغض النظر عن لقبه).
2. `job_title_permissions`: "محاسب عام" → `fin-gl-jou.view` (زيادة عن اللزوم هنا، متضاف للتوضيح — منحة على مستوى اللقب بتشتغل حتى برّه إدارة المالية).
3. `job_title_grade_permissions`: "محاسب عام" @ درجة Senior → `fin-gl-jou.post`. junior شايل نفس اللقب عنده `view` بس مش `post`؛ ترقيته لـ Senior (تعديل عادي على حقل في `Employee`) بيفتح `post` بلا أي تعديل تاني.
4. `job_title_grade_roles`: لقب "مدير مالي"، بلا قيد درجة → دور `finance_manager` متلصق بكل الدرجات.

## اللي المحرك ده متعمّد ماعملوش

- **مفيش جدول صلاحيات على مستوى الحقل.** رؤية أي حقل هي `Column::visible()` / callback في `ViewField` محكوم بفحص `can()` عادي على صلاحية عادية — مفيش آلية جديدة اتطلبت بمجرد ما شكل "الأفعال القياسية + الأفعال الخاصة" كان موجود.
- **مفيش منح مؤقتة لكل طلب.** المنحة دايمًا جاية من سلسلة الإدارة/اللقب/الدرجة؛ مفيش جدول "امنح المستخدم ده الصلاحية دي النهاردة بس". يتضاف بس لو فيه متطلب فعلي.
