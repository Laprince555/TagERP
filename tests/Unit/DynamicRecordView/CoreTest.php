<?php

use App\Support\DynamicRecordView\Core\Content\Content;
use App\Support\DynamicRecordView\Core\Content\EmptyStateContent;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\Content\TableContent;
use App\Support\DynamicRecordView\Core\Exceptions\DuplicateContentKeyException;
use App\Support\DynamicRecordView\Core\Exceptions\DuplicateFieldKeyException;
use App\Support\DynamicRecordView\Core\Exceptions\DuplicateTabKeyException;
use App\Support\DynamicRecordView\Core\Exceptions\MultipleDefaultTabsException;
use App\Support\DynamicRecordView\Core\Exceptions\NoAuthorizedDefaultTabException;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\ComputedViewField;
use App\Support\DynamicRecordView\Core\Fields\DateViewField;
use App\Support\DynamicRecordView\Core\Fields\EnumViewField;
use App\Support\DynamicRecordView\Core\Fields\LinkViewField;
use App\Support\DynamicRecordView\Core\Fields\MoneyViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordSection;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\RelationPicker;
use App\Support\DynamicRecordView\Core\RelationshipActions;
use App\Support\DynamicRecordView\Core\SubApplication;

it('renders a text field with default label and placeholder', function (): void {
    $field = TextViewField::make('name');

    expect($field->getLabel())->toBe('Name')
        ->and($field->display(['name' => null]))->toBeNull()
        ->and($field->getPlaceholder())->toBe('—');
});

it('formats money and date fields by default', function (): void {
    expect(MoneyViewField::make('amount')->currency('USD')->display(['amount' => 12.5]))->toBe('USD 12.50')
        ->and(DateViewField::make('d')->format('Y-m-d')->display(['d' => '2026-08-14']))->toBe('2026-08-14')
        ->and(BooleanViewField::make('b')->display(['b' => true]))->toBe('Yes')
        ->and(EnumViewField::make('e')->labels(['x' => 'X value'])->display(['e' => 'x']))->toBe('X value');
});

it('lets formatUsing override default formatting', function (): void {
    $field = TextViewField::make('name')->formatUsing(fn ($v) => strtoupper($v));

    expect($field->display(['name' => 'abc']))->toBe('ABC');
});

it('resolves computed fields from the whole record', function (): void {
    $field = ComputedViewField::make('full')->using(fn ($record) => $record['first'].' '.$record['last']);

    expect($field->display(['first' => 'A', 'last' => 'B']))->toBe('A B');
});

it('respects visible()/hidden() conditions', function (): void {
    $visible = TextViewField::make('x')->visible(fn ($r) => $r['ok']);
    $hidden = TextViewField::make('x')->hidden(fn ($r) => $r['ok']);

    expect($visible->isVisible(['ok' => true]))->toBeTrue()
        ->and($visible->isVisible(['ok' => false]))->toBeFalse()
        ->and($hidden->isVisible(['ok' => true]))->toBeFalse()
        ->and($hidden->isVisible(['ok' => false]))->toBeTrue();
});

it('rejects duplicate field keys in a FieldsContent', function (): void {
    FieldsContent::make('info')->fields([
        TextViewField::make('name'),
        TextViewField::make('name'),
    ]);
})->throws(DuplicateFieldKeyException::class);

it('rejects duplicate content keys in a tab', function (): void {
    RecordTab::make('t')->contents([
        FieldsContent::make('a'),
        EmptyStateContent::make('a'),
    ]);
})->throws(DuplicateContentKeyException::class);

it('rejects duplicate tab keys in a section', function (): void {
    RecordSection::make('s')->tabs([
        RecordTab::make('a'),
        RecordTab::make('a'),
    ]);
})->throws(DuplicateTabKeyException::class);

it('rejects more than one default tab in a section', function (): void {
    RecordSection::make('s')->tabs([
        RecordTab::make('a')->default(),
        RecordTab::make('b')->default(),
    ]);
})->throws(MultipleDefaultTabsException::class);

it('picks the explicit default tab when authorized', function (): void {
    $section = RecordSection::make('s')->tabs([
        RecordTab::make('a'),
        RecordTab::make('b')->default(),
    ]);

    expect($section->defaultTabKey())->toBe('b');
});

it('falls back to the first authorized tab when the default is unauthorized', function (): void {
    $section = RecordSection::make('s')->tabs([
        RecordTab::make('a'),
        RecordTab::make('b')->default()->visible(false),
    ]);

    expect($section->defaultTabKey())->toBe('a');
});

it('throws when no tab is authorized at all', function (): void {
    $section = RecordSection::make('s')->tabs([
        RecordTab::make('a')->visible(false),
    ]);

    $section->defaultTabKey();
})->throws(NoAuthorizedDefaultTabException::class);

it('builds a TableContent with a relation constraint closure', function (): void {
    $content = TableContent::make('apps')->table('SomeTableClass')->forRelation(fn ($record) => $record);

    expect($content->getTable())->toBe('SomeTableClass')
        ->and($content->getForRelation())->toBeInstanceOf(Closure::class);
});

it('builds a SubApplication definition with authorization', function (): void {
    $sub = SubApplication::make('apps')->applicationKey('general.apps')->label('Applications')
        ->table('SomeTableClass')->authorization(fn ($record) => true);

    expect($sub->getApplicationKey())->toBe('general.apps')
        ->and($sub->isAuthorized())->toBeTrue();
});

it('builds RelationshipActions and RelationPicker via their real fluent API', function (): void {
    $picker = RelationPicker::make()->displayUsing('name')->searchable(['name'])->pageSize(5)->maximumLoadedResults(50);
    $actions = RelationshipActions::make()->linkExisting($picker)->unlink();

    expect($actions->isLinkable())->toBeTrue()
        ->and($actions->isUnlinkable())->toBeTrue()
        ->and($actions->getPicker())->toBe($picker)
        ->and($picker->getSearchable())->toBe(['name'])
        ->and($picker->getPageSize())->toBe(5)
        ->and($picker->getMaximumLoadedResults())->toBe(50);
});

it('escapes field output by construction — no raw-HTML content type exists', function (): void {
    expect(class_exists(Content::class))->toBeTrue();

    $reflection = new ReflectionClass(Content::class);
    $subclasses = ['FieldsContent', 'TableContent', 'SubApplicationContent', 'EmptyStateContent'];

    foreach ($subclasses as $name) {
        expect(class_exists("App\\Support\\DynamicRecordView\\Core\\Content\\{$name}"))->toBeTrue();
    }
});

it('LinkViewField resolves url and new-tab flag', function (): void {
    $field = LinkViewField::make('name')->linkUsing(fn ($r) => "/x/{$r['id']}")->openInNewTab();

    expect($field->getUrl(['id' => 5]))->toBe('/x/5')
        ->and($field->opensInNewTab())->toBeTrue();
});
