@include('crud::fields.inc.wrapper_start')
    <div class="rounded border p-3" data-document-profile="{{ $field['profile'] }}">
        <dl class="row mb-0">
            @foreach ($field['items'] as $item)
                <div class="col-12 col-md-6 mb-2">
                    <dt class="text-muted fw-normal small">{{ $item['label'] }}</dt>
                    <dd class="mb-0 text-break" data-profile-key="{{ $item['key'] }}">{{ $item['value'] ?: '—' }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
    <p class="help-block">{{ $field['hint'] }}</p>
@include('crud::fields.inc.wrapper_end')
