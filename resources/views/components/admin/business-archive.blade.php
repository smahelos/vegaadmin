<form method="POST" action="{{ route('admin.businesses.archive', ['id' => $businessId]) }}" class="d-inline">
    @csrf
    @if ($replacements !== [])
        <label for="replacement-business-{{ $businessId }}">{{ __('businesses.fields.new_default') }}</label>
        <select name="replacement_business_id" id="replacement-business-{{ $businessId }}" class="form-select form-select-sm d-inline w-auto" required>
            <option value="">{{ __('businesses.titles.choose_new_default') }}</option>
            @foreach ($replacements as $replacement)
                <option value="{{ $replacement->id->value }}">{{ $replacement->profile->name }}</option>
            @endforeach
        </select>
    @endif
    <button type="submit" class="btn btn-sm btn-link" title="{{ __('parties.actions.archive') }}">
        <i class="la la-archive"></i> {{ __('parties.actions.archive') }}
    </button>
</form>
