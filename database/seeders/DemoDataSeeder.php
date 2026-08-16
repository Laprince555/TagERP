<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\AccountCategory;
use Modules\Finance\Models\GeneralLedger\AccountGroup;
use Modules\Finance\Models\GeneralLedger\AccountNature;
use Modules\Finance\Models\GeneralLedger\Chart;
use Modules\Finance\Models\GeneralLedger\CostCenter;
use Modules\Finance\Models\GeneralLedger\ExchangeRate;
use Modules\Finance\Models\GeneralLedger\FiscalPeriod;
use Modules\Finance\Models\GeneralLedger\FiscalYear;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Modules\Finance\Models\GeneralLedger\JournalLine;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\General\Models\World\Companies\Company;
use Modules\General\Models\World\Currency;
use Modules\General\Models\World\People\Person;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Models\OrganizationStructure\Branch;
use Modules\HR\Models\OrganizationStructure\Department;
use Modules\HR\Models\OrganizationStructure\Entity;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * Fills every Application with enough coherent business data to click
 * through the UI for real. Deliberately NOT called from DatabaseSeeder:
 * that one seeds structure (modules, applications, permissions, world
 * reference data) and must stay fast and clean.
 *
 *   php artisan db:seed --class=Database\\Seeders\\DemoDataSeeder
 *
 * Run it after DatabaseSeeder, and only once — it appends rather than
 * truncates, so a second run doubles everything.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $currency = Currency::query()->where('code', 'EGP')->first() ?? Currency::factory()->create();

        $company = Company::factory()->create(['name' => 'Tag Holding']);

        $holding = Entity::factory()->create([
            'name' => 'Tag Holding',
            'company_id' => $company->id,
            'is_holding' => true,
            'currency_id' => $currency->id,
        ]);

        $entities = Entity::factory()->count(2)->create([
            'company_id' => $company->id,
            'parent_entity_id' => $holding->id,
            'currency_id' => $currency->id,
        ]);

        $this->seedOrganization($holding, $entities->all());
        $this->seedGeneralLedger($holding, $currency);

        $this->command?->info('Demo data seeded.');
    }

    /**
     * One shared org chain per entity, then employees pointing at it.
     * Employees are created directly rather than through EmployeeFactory
     * because that factory builds a throwaway entity/branch/department per
     * employee — fine for a test asserting one record, but it would leave
     * dozens of junk entities in a database meant for clicking around.
     *
     * @param  Entity[]  $entities
     */
    protected function seedOrganization(Entity $holding, array $entities): void
    {
        $grades = JobGrade::factory()
            ->count(4)
            ->sequence(
                ['name' => 'Junior', 'level' => 10],
                ['name' => 'Mid', 'level' => 20],
                ['name' => 'Senior', 'level' => 30],
                ['name' => 'Lead', 'level' => 40],
            )
            ->create();

        foreach ([$holding, ...$entities] as $entity) {
            $branch = Branch::factory()->create([
                'entity_id' => $entity->id,
                'is_main' => true,
                'name' => $entity->name.' HQ',
            ]);

            $departments = Department::factory()
                ->count(3)
                ->sequence(
                    ['name' => 'Finance'],
                    ['name' => 'Operations'],
                    ['name' => 'Human Resources'],
                )
                ->create();

            foreach ($departments as $department) {
                $department->attachToEntity($entity, $branch);

                $titles = JobTitle::factory()->count(2)->create(['department_id' => $department->id]);

                foreach ($titles as $title) {
                    $title->jobGrades()->attach($grades->pluck('id')->all());

                    foreach (Person::factory()->count(2)->create() as $person) {
                        Employee::create([
                            'employee_number' => 'EMP-'.str_pad((string) (Employee::count() + 1), 5, '0', STR_PAD_LEFT),
                            'person_id' => $person->id,
                            'entity_id' => $entity->id,
                            'branch_id' => $branch->id,
                            'department_id' => $department->id,
                            'job_title_id' => $title->id,
                            'job_grade_id' => $grades->random()->id,
                            'entity_scope' => 'branch',
                            'department_scope' => 'department',
                            'gross_salary' => fake()->randomFloat(2, 8000, 60000),
                            'status' => 'active',
                            'hire_date' => fake()->dateTimeBetween('-5 years', 'now'),
                        ]);
                    }
                }
            }
        }
    }

    protected function seedGeneralLedger(Entity $entity, Currency $currency): void
    {
        $categories = collect(AccountNature::cases())->map(
            fn (AccountNature $nature) => AccountCategory::factory()->create([
                'name' => str($nature->value)->headline()->toString(),
                'nature' => $nature,
            ]),
        );

        $chart = Chart::factory()->create(['name' => 'Main Chart of Accounts']);

        // A parent account per category, then postable children under it —
        // a flat list of 40 unrelated accounts exercises none of the tree UI.
        foreach ($categories as $category) {
            $parent = Account::factory()->create([
                'name' => $category->name,
                'category_id' => $category->id,
            ]);

            $children = Account::factory()->count(5)->create([
                'parent_id' => $parent->id,
                'category_id' => $category->id,
            ]);

            $chart->accounts()->attach([$parent->id, ...$children->pluck('id')->all()]);
        }

        $ledger = Ledger::factory()->create([
            'name' => $entity->name.' Primary Ledger',
            'entity_id' => $entity->id,
            'chart_id' => $chart->id,
            'base_currency_id' => $currency->id,
            'is_primary' => true,
        ]);

        $fiscalYear = FiscalYear::factory()->create([
            'name' => 'FY 2026',
            'entity_id' => $entity->id,
            'start_date' => now()->setDate(2026, 1, 1)->startOfDay(),
            'end_date' => now()->setDate(2026, 12, 31)->endOfDay(),
        ]);

        $periods = collect(range(1, 12))->map(function (int $month) use ($fiscalYear): FiscalPeriod {
            $start = now()->setDate(2026, $month, 1)->startOfDay();

            return FiscalPeriod::factory()->create([
                'name' => $start->format('M Y'),
                'fiscal_year_id' => $fiscalYear->id,
                'sequence' => $month,
                'start_date' => $start,
                'end_date' => $start->copy()->endOfMonth(),
            ]);
        });

        $books = JournalBook::factory()
            ->count(3)
            ->sequence(
                ['name' => 'General Journal', 'sequence_prefix' => 'GJ'],
                ['name' => 'Sales Journal', 'sequence_prefix' => 'SJ'],
                ['name' => 'Purchases Journal', 'sequence_prefix' => 'PJ'],
            )
            ->create();

        CostCenter::factory()->count(6)->create();

        AccountGroup::factory()->count(3)->create();

        ExchangeRate::factory()->count(5)->create([
            'from_currency_id' => $currency->id,
            'to_currency_id' => Currency::query()->where('id', '!=', $currency->id)->value('id') ?? Currency::factory()->create()->id,
        ]);

        $postable = Account::query()->whereNotNull('parent_id')->get();

        foreach ($periods as $period) {
            for ($i = 0; $i < 3; $i++) {
                $journal = Journal::factory()->inPeriod($ledger, $period)->create([
                    'journal_book_id' => $books->random()->id,
                    'description' => fake()->sentence(),
                ]);

                // Two balanced lines: an unbalanced journal cannot be posted,
                // so it would make the posting screens untestable.
                $amount = fake()->randomFloat(2, 500, 50000);
                $pair = $postable->random(2);

                foreach ($pair->values() as $index => $account) {
                    $isDebit = $index === 0;

                    JournalLine::factory()->create([
                        'journal_id' => $journal->id,
                        'line_number' => $index + 1,
                        'account_id' => $account->id,
                        'currency_id' => $currency->id,
                        'debit' => $isDebit ? $amount : 0,
                        'credit' => $isDebit ? 0 : $amount,
                        'base_debit' => $isDebit ? $amount : 0,
                        'base_credit' => $isDebit ? 0 : $amount,
                    ]);
                }
            }
        }
    }
}
