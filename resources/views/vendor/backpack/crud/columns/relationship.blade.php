{{-- Relationship column (Billex implementation): the free `select` column for single relations, `select_multiple` for many. --}}
@php
    $relationType = $column['relation_type'] ?? class_basename($entry->{$column['entity'] ?? $column['name']}());
@endphp

@include($crud->guessIfFieldHasMultipleFromRelationType($relationType) ? 'crud::columns.select_multiple' : 'crud::columns.select')
