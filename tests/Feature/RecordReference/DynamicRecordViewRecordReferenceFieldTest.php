<?php

use App\Livewire\DynamicRecordView\RecordView;
use App\Models\User;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\ViewException;
use Livewire\Livewire;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Modules\General\System\Application;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;

class TestDynamicRecordView extends DynamicRecordView
{
    protected string $viewKey = 'test.dynamic-record-view';

    public function model(): string
    {
        return City::class;
    }

    public function query(): Builder
    {
        return City::query();
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function tabs(): array
    {
        return [
            RecordTab::make('overview')
                ->contents([
                    FieldsContent::make('details')
                        ->fields([
                            RecordReferenceViewField::make('country-ref')
                                ->label('Country Ref')
                                ->applicationCode('gen-wld-ctr')
                                ->relation('country')
                                ->variant(RecordReferenceVariant::Tag),
                        ]),
                ]),
        ];
    }
}

class TestRecordViewComponent extends RecordView
{
    protected function recordViewKey(): string
    {
        return 'test.dynamic-record-view';
    }
}

beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new WorldApplicationsSeeder)->run();

    app(RecordViewRegistry::class)->register('test.dynamic-record-view', TestDynamicRecordView::class);

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->country = Country::create([
        'name' => 'Egypt',
        'iso2' => 'EG',
        'iso3' => 'EGY',
        'phone_code' => '20',
        'region' => 'Africa',
        'subregion' => 'Northern Africa',
        'status' => 1,
    ]);

    $this->city = City::create([
        'country_id' => $this->country->id,
        'state_id' => 0,
        'name' => 'Cairo',
        'country_code' => 'EG',
    ]);
});

it('renders the RecordReferenceViewField Tag variant correctly', function (): void {
    Livewire::test(TestRecordViewComponent::class, ['recordId' => $this->city->id])
        ->assertOk()
        ->assertSee('Egypt')
        ->assertSee('gen-wld-ctr');
});

it('denies access and fails closed if the application is disabled', function (): void {
    $application = Application::where('code', 'gen-wld-ctr')->first();
    $application->update(['is_active' => false]);

    // Since the field checks applicationAccessible, a disabled application means the field
    // will render the placeholder instead of the authorized Tag link.
    Livewire::test(TestRecordViewComponent::class, ['recordId' => $this->city->id])
        ->assertOk()
        ->assertDontSee('href="http') // The tag link should not render
        ->assertSee('—'); // Placeholder
});

it('throws InvalidModelException when validation fails due to model/provider mismatch', function (): void {
    // Register a mismatched field (e.g. self-reference pointing to City instead of Country)
    class MismatchedRecordView extends DynamicRecordView
    {
        protected string $viewKey = 'test.dynamic-record-view-mismatch';

        public function model(): string
        {
            return City::class;
        }

        public function query(): Builder
        {
            return City::query();
        }

        public function title(mixed $record): string
        {
            return (string) $record->name;
        }

        public function tabs(): array
        {
            return [
                RecordTab::make('overview')
                    ->contents([
                        FieldsContent::make('details')
                            ->fields([
                                RecordReferenceViewField::make('self-ref')
                                    ->applicationCode('gen-wld-ctr'), // expects Country, but City model is used
                            ]),
                    ]),
            ];
        }
    }

    app(RecordViewRegistry::class)->register('test.dynamic-record-view-mismatch', MismatchedRecordView::class);

    class MismatchedRecordViewComponent extends RecordView
    {
        protected function recordViewKey(): string
        {
            return 'test.dynamic-record-view-mismatch';
        }
    }

    try {
        Livewire::test(MismatchedRecordViewComponent::class, ['recordId' => $this->city->id]);
    } catch (ViewException $e) {
        expect($e->getMessage())->toContain('points to [Nnjeim\World\Models\City], expected [Nnjeim\World\Models\Country]');

        return;
    }

    $this->fail('Expected ViewException was not thrown.');
});
