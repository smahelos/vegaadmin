@extends('layouts.frontend')

@section('content')
<livewire:party.business-detail :business-id="$businessId" />
@endsection
