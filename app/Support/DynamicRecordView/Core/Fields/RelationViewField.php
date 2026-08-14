<?php

namespace App\Support\DynamicRecordView\Core\Fields;

/**
 * Displays an attribute off a related record via dotted path, e.g.
 * RelationViewField::make('subModule.name'). Resolution goes through
 * data_get(), so it works whether the relation is already eager-loaded on
 * the record (the expected usage — see DynamicRecordView::query()) or is a
 * plain array.
 */
class RelationViewField extends Field {}
