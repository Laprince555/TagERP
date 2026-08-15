# محرك الصلاحيات ونطاق البيانات

نظام تحكم في الوصول بمحركين: محرك يقرر أنهي **شاشات/أفعال** يقدر المستخدم يوصلها (الصلاحيات الوظيفية)، ومحرك تاني مستقل تمامًا يقرر أنهي **صفوف بيانات** يقدر نفس المستخدم يشوفها بمجرد دخوله الشاشة (نطاق البيانات). كل ميزة موثّقة هنا متنفذة وعليها اختبارات Pest إلا لو اتكتب صراحة 🔮 مخطط لها أو ❌ غير مدعومة — راجع [testing.md](testing) لمعرفة الموثّق فعليًا.

## ليه محركين مش محرك واحد

فحص صلاحية واحد بيجاوب "المستخدم ده يقدر يشوف الفواتير؟" مش هيقدر يجاوب كمان "فواتير أنهي شركة؟" — دمج السؤالين في آلية واحدة هو أشهر سبب لانهيار التحكم في الوصول في أي ERP (باج في النطاق بيبان كأنه باج في الصلاحية والعكس، وإصلاح واحد بيكسر التاني بصمت). النظام ده بيفصلهم بنيويًا:

| السؤال | الآلية | الكلاس الأساسي |
|---|---|---|
| أنهي شاشات/أفعال يقدر يفتحها؟ | أدوار/صلاحيات Spatie، بتتزامن تلقائيًا من الهيكل التنظيمي | `App\Support\Organization\EmployeePermissionSynchronizer` |
| أنهي صفوف بيانات يشوفها؟ | مجموعة IDs محسوبة ومُخزّنة (entity/branch/department) | `App\Support\Organization\OrganizationScopeResolver` |

الفحصين بيتطبقوا على كل طلب، بشكل مستقل. مدير مبيعات ممكن يكون عنده رؤية بيانات كاملة على الشركة كلها وبرضه يرجّع `can('fin-gl-jou.view') === false` — رتبته في الهيكل التنظيمي مالهاش علاقة بالصلاحيات الوظيفية، ومنحة وظيفية واسعة مالهاش علاقة بأنهي صفوف هترجع من أي استعلام.

## اللي هو فعليًا

- **الهيكل التنظيمي** (`Modules/HR/Models/OrganizationStructure/`) — `Entity`, `Branch`, `Department` (كتالوج عام، مش صف لكل شركة — شوف [data-scope.md](data-scope#departments-are-a-shared-catalog)), `JobTitle`, `JobGrade`. `Entity`/`Department` بيستخدموا عمود `path` (materialized path) لاستعلامات الفروع التابعة.
- **السجل المحوري** — `Modules\HR\Models\EmployeeManagement\Employee` هو الجسر الوحيد بين `User` مسجّل دخول والهيكل التنظيمي. شوف [data-scope.md](data-scope).
- **محرك نطاق البيانات** (`app/Support/Organization/`) — `OrganizationScopeResolver`, `OrganizationScope` (كائن النتيجة)، `OrganizationVersion` (إبطال الكاش)، `OrganizationScopeConstraint` (الـ Eloquent global scope)، و trait `App\Models\Concerns\ScopedToOrganization` اللي أي موديل يستخدمها.
- **محرك الصلاحيات الوظيفية** — `php artisan permissions:sync` بيولّد كل صلاحيات Spatie من أكواد `modules`/`sub_modules`/`applications` الموجودة أصلاً؛ `department_permissions` / `job_title_permissions` / `job_title_grade_permissions` / `job_title_grade_roles` هي جداول المنح؛ `EmployeePermissionSynchronizer` هو الكاتب الوحيد لـ `model_has_permissions`/`model_has_roles`؛ `php artisan hr:permissions:reconcile` هو حارس الانحراف.

## جدول الميزات

| الميزة | الحالة |
|---|---|
| هيكل تنظيمي (Entity/Branch/Department/JobTitle/JobGrade) بشجرة materialized-path | ✅ متنفذ |
| إدارات كتالوج عام مشترك بين الشركات (pivot `department_entity`) | ✅ متنفذ |
| `Employee` كجسر وحيد بين User والهيكل التنظيمي | ✅ متنفذ |
| نطاق بيانات بُعدين (`entity_scope` × `department_scope`، كتقاطع) | ✅ متنفذ |
| تخزين مؤقت لنتيجة النطاق مع إبطال بعدّاد واحد (`OrganizationVersion`) | ✅ متنفذ |
| trait `ScopedToOrganization` — تفعيل اختياري لأي موديل في أي موديول | ✅ متنفذ |
| رفض كامل عند عدم وجود مستخدم مسجّل / موظف نشط | ✅ متنفذ |
| رفض آمن (مش انهيار) عند حذف كيان/إدارة بالحذف الناعم أثناء الشجرة | ✅ متنفذ |
| تخطي `super_admin` للمحركين الاتنين بشكل مستقل | ✅ متنفذ |
| توليد الصلاحيات تلقائيًا من أكواد الهيكل الملاحي | ✅ متنفذ — `permissions:sync` |
| جداول منح: قطاع / لقب وظيفي / لقب+درجة فما فوق / أدوار مسمّاة | ✅ متنفذ |
| مزامنة Spatie تلقائية عند التعيين/النقل/الترقية/إنهاء الخدمة | ✅ متنفذ |
| كشف وتصحيح الانحراف (`hr:permissions:reconcile`، بخيار `--dry-run`) | ✅ متنفذ |
| فلترة شجرة التنقّل (القائمة الجانبية) بالصلاحية على الثلاث مستويات | ✅ متنفذ |
| صلاحيات على مستوى الحقل (زي إخفاء عمود مالي عن ألقاب معينة) | ✅ متنفذ — عن طريق `Column::visible()` / callbacks الحقول الموجودة أصلاً، بلا آلية جديدة |
| نطاق "سجلاته هو بس" (Employee Self Service) كقيمة نطاق مستقلة | ✅ متنفذ (القيمة بس — شاشات submodule الخدمة الذاتية لسه ما اتبنتش) |
| مدير إدارة بيرث تلقائيًا نطاق على الإدارات الفرعية | ✅ متنفذ — `department_scope = department_tree` |
| منح نطاق لمدير إقليمي/متعدد الفروع بعيد عن `entity_scope` القياسي | 🔮 مخطط له — `hr_employee_scope_grants` (جدول منح إضافي) |
| سجل تاريخي لتغيّر الوظيفة/الراتب عبر الوقت | 🔮 مخطط له — `employee_assignment_history` |
| دورات اعتماد مستندية (خطوات متعددة، استثناءات) | 🔮 مخطط له — محرك منفصل، مؤجّل عمدًا |
| شاشات تعديل (Edit) لسجلات الهيكل التنظيمي | ❌ غير متنفذ — محرك `DynamicForm` إنشاء فقط في هذه المرحلة |
| واجهة لإدارة ربط `department_entity` / `job_title_grade` | ❌ غير متنفذ — الربط عن طريق `Department::attachToEntity()` / `JobTitle::jobGrades()->attach()` مباشرة؛ تبويبات "الشركات"/"الدرجات" في صفحة العرض للقراءة فقط |

الجدول ده لازم يفضل صادق — حدّثه لما حالة صف تتغيّر فعليًا، مش بس لما ميزة تتشحن.

## التثبيت

مفيش حاجة تتثبّت. مبني كامل على حزم موجودة أصلاً: Laravel 13، `spatie/laravel-permission` 8.3 (كانت مثبّتة وغير مستخدمة قبل المحرك ده)، Livewire 4، Pest 5. مفيش أي حزمة Composer جديدة اتضافت.

## فين تكمل

- [data-scope.md](data-scope) — الهيكل التنظيمي، سجل `Employee`، `entity_scope` / `department_scope`، الكاش والإبطال، trait `ScopedToOrganization`.
- [functional-permissions.md](functional-permissions) — توليد الصلاحيات، جداول المنح، المزامنة، تصحيح الانحراف.
- [testing.md](testing) — مصفوفة السيناريوهات كاملة وفين كل واحد منها مُثبت.
- المستخدمين: [../user-guide/permissions.md](../user-guide/permissions) — منح/سحب صلاحية موظف، بكلام عادي بلا كود.
