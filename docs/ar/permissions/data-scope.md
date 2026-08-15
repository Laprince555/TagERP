# محرك نطاق البيانات

بيجاوب على سؤال "أنهي صفوف يقدر المستخدم ده يشوفها"، بغض النظر عن كونه مسموحله يفتح الشاشة أصلاً (ده موضوع [functional-permissions.md](functional-permissions)).

## الهيكل التنظيمي

`Modules/HR/Models/OrganizationStructure/`:

- **`Entity`** — شركة بتديرها فعليًا (مش كل صف في `companies` — الجدول ده فيه موردين/عملاء كمان). `parent_entity_id` + عمود `path` (materialized path، زي `"/1/4/9/"`) بيخلوا `->descendantsAndSelf()` يلاقي كل الكيانات التابعة باستعلام `LIKE` واحد مفهرس — مفيش أبدًا recursive CTE ولا لفّة N+1 على الآباء.
- **`Branch`** — تابع لـ `Entity` واحدة؛ فرع رئيسي واحد بس (`is_main = true`) لكل كيان (متفروض في `Branch::booted()`، مش في قاعدة البيانات — MySQL معندهاش partial unique index).
- **`Department`** — **صف كتالوج عام على مستوى المجموعة** (شوف تحت)، مش صف لكل شركة. نفس نمط الشجرة بـ materialized path زي `Entity`.
- **`JobTitle`** / **`JobGrade`** — عامين على مستوى المجموعة، مش لكل كيان. `JobGrade.level` سلّم رقمي واحد مشترك (عن طريق `job_title_grade`)، فمقارنة "الدرجة دي فما فوق" دايمًا بتتم جوه سلّم اللقب نفسه — senior في لقب معيّن مايتقارنش أبدًا بـ senior في لقب تاني.

### الإدارات كتالوج مشترك

جدول `departments` **معندوش عمود `entity_id`**. "المالية" صف واحد بس، مشترك بين أي شركة بتستخدمه. أنهي شركات/فروع فعّلت الإدارة دي فعليًا بيتحدد في pivot `department_entity` (`department_id`, `entity_id`, `branch_id` اختياري — فاضي معناها الشركة كلها). ده مقصود: ربط إدارة بشركة وعزل نطاق البيانات جايين من مكانين مختلفين، ودمجهم هو الغلطة اللي الشكل ده بيتفاداها — شوف اختبار ["مدير مالي في شركة وليدة مش بياخد نطاق على شركة الأم، حتى لو بيستخدم نفس صف الإدارة"](testing) اللي بيثبت الكلام ده.

## السجل المحوري: `Employee`

`Modules\HR\Models\EmployeeManagement\Employee` هو **الجسر الوحيد** بين `User` مسجّل دخول والهيكل التنظيمي:

```
employees: person_id, user_id (اختياري), entity_id, branch_id, department_id,
           job_title_id, job_grade_id, entity_scope, department_scope, status, ...
```

`user_id` اختياري — مش كل موظف بيدخل النظام (عامل مصنع مثلاً). `User` بلا سطر `Employee` نشط (`status = 'active'`) بيتحسب رفض كامل، في كل مكان — ده مقصود من التصميم، مش باج نصلّحه بـ fallback.

## بُعدين نطاق مستقلين

| العمود | القيم | بيجاوب على |
|---|---|---|
| `entity_scope` | `own` \| `branch` \| `entity` \| `entity_tree` | أنهي شركات/فروع (أفقي) |
| `department_scope` | `own` \| `department` \| `department_tree` \| `all` | أنهي جزء من الهيكل الإداري (رأسي) |

الصفوف المرئية = **تقاطع** البُعدين، مش اتحاد. ده اللي بيخلّي آلية واحدة تعبّر عن كل الحالات اللي اتناقشت أثناء تصميم النظام:

- مدير مالي على مستوى المجموعة (`entity_tree` + `department_tree`) بيشوف القطاع المالي في الشركة الأم وكل الشركات التابعة.
- نفس المدير، لو متمركز في شركة تابعة بس (`entity` + `department_tree`)، بيشوف القطاع المالي في الشركة دي بس — مش الأم، ومش الشقيقة.
- موظف في فرع مش رئيسي (`branch`) محصور بالفرع ده، حتى لسجلات تانية في نفس الكيان بالظبط.
- `own` **مش** "فرعه" — هي صفر توسّع في entity/branch خالص. نطاق الخدمة الذاتية (موظف بيشوف طلبات إجازته هو) بُعد مختلف تمامًا (فلترة بـ `employee_id`)، مش هيتلخبط أبدًا مع `branch`.

`department_scope = department_tree` بيمشي على المسار الخاص بالإدارة نفسها — بلا علاقة بالكيان، مش متأثر بأنهي شركات بتشارك نفس صف الإدارة.

## `OrganizationScopeResolver`

```php
$scope = app(OrganizationScopeResolver::class)->resolve($user); // OrganizationScope
```

بيرجّع كائن `OrganizationScope`: `entityIds`, `branchIds`, `departmentIds`، كل واحدة إما `array<int>` (قائمة مسموحة محددة) أو `null` (**بلا قيد** — محجوزة لـ `super_admin`). مصفوفة فاضية (`[]`) معناها رفض كامل — الفرق بين `null` و`[]` هو حدود الصلاحية نفسها؛ متدمجهمش أبدًا.

ترتيب الحل:

1. دور `super_admin` → `OrganizationScope::unrestricted()`، بلا أي بحث إضافي.
2. مفيش سطر `Employee` نشط لليوزر ده → `OrganizationScope::denyAll()`.
3. غير كده، `entity_scope` بيتحل لـ `[entityIds, branchIds]` و`department_scope` بيتحل لـ `departmentIds` كل واحد لوحده، حسب الجدول فوق.

`explain(User $user): array{scope, trace}` بيعمل نفس الحل **بدون كاش**، مع شرح كل خطوة — الأداة المخصصة لسؤال "ليه المستخدم ده مش شايف السجل ده؟"، مش لمسار الطلب الأساسي.

### الكاش والإبطال

`resolve()` بيتخزّن مؤقتًا تحت `org_scope:{user_id}:v{version}` بـ `rememberForever`. `OrganizationVersion` عدّاد عام واحد — زيادته مرة (عن طريق hooks الـ `saved`/`deleted` بتاعة `Entity`, `Branch`, `Department`, أو `Employee`) بتلغي كاش **كل** المستخدمين منطقيًا في نفس اللحظة، بدل ما ندوّر مين اتأثر بتعديل الشجرة. القراءات من غير كتابة بينهم مبتزودش العدّاد أبدًا (شوف اختبار منع الاهتزاز في `testing.md`) — `rememberForever` فعليًا مابيعملش استعلام تاني لحد ما حاجة تتغيّر فعلًا.

## `ScopedToOrganization` — نقطة التكامل

```php
use App\Models\Concerns\ScopedToOrganization;

class Invoice extends Model
{
    use ScopedToOrganization;
}
```

ده كل التكامل المطلوب لأي موديل، في أي موديول. الـ trait بيضيف global scope (`App\Support\Organization\OrganizationScopeConstraint`) بيفحص أنهي أعمدة من `entity_id`/`branch_id`/`department_id` موجودة فعليًا على جدول الموديل (قائمة أعمدة مخزّنة مؤقتًا لكل جدول، مش بتتستعلم كل صف) وبيفلتر بالتقاطع فوق — موديل عنده `entity_id` بس مش هيتأثر ببُعد الإدارة، والعكس صحيح.

مفيش مستخدم مسجّل دخول (queue worker، أمر console، مهمة مجدولة) → القيد بيرفض كل حاجة (`whereRaw('1 = 0')`). الوصول الشامل المشروع لازم يستثنى صراحة:

```php
Employee::withoutGlobalScope(OrganizationScopeConstraint::class)->...
```

`OrganizationScopeResolver` نفسه بيعمل بالظبط كده داخليًا لما بيدوّر على سطر `Employee` بتاع المستخدم **الحالي** نفسه — حل النطاق لازم مايعتمدش أبدًا على نطاق بيتحسب هو نفسه من نفس السطر، وإلا كل بحث هيدخل في حلقة لا نهائية.

## سقوف معلنة (متعمدة، مش منسية)

- **منح لمدير إقليمي/متعدد الفروع** أوسع من قيمة واحدة في `entity_scope` — زي مدير على ٣ فروع محددين بالاسم — محتاج جدول إضافي `hr_employee_scope_grants`. لسه ماتبنيش؛ `entity_scope` لوحده بيغطي كل المطلوب حاليًا.
- **صلاحيات على مستوى الحقل** (زي إخفاء تكلفة مشروع عن مهندس، وإظهارها لمدير المشروع) مش آلية جديدة — هي `Column::visible()` / callbacks رؤية `ViewField` اللي محركات Dynamic Table/Record View بتدعمها أصلاً، محكومة بالصلاحية *الوظيفية* الخاصة بفعل الحقل ده بالتحديد.
