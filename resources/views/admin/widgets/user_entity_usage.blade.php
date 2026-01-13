{{-- User Entity Usage Widget --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title">
            <i class="las la-user-chart"></i> Your Current Usage
        </h5>
    </div>
    <div class="card-body">
        @if(count($userUsage) > 0)
        @foreach($userUsage as $entityType => $usage)
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <span class="font-weight-bold">{{ ucfirst($entityType) }}s</span>
                <span class="text-muted">
                    {{ $usage['current_usage'] }} / {{ $usage['max_allowed'] ?? '∞' }}
                </span>
            </div>

            @php
            $percentage = $usage['max_allowed'] ?
            min(100, ($usage['current_usage'] / $usage['max_allowed']) * 100) : 0;
            $colorClass = $percentage > 80 ? 'bg-danger' :
            ($percentage > 60 ? 'bg-warning' : 'bg-success');
            @endphp

            <div class="progress" style="height: 8px;">
                <div class="progress-bar {{ $colorClass }}" role="progressbar" style="width: {{ $percentage }}%"
                    aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>

            <small class="text-muted">
                {{ ucfirst($usage['period_type']) }} limit
                @if($usage['period_end'])
                (resets {{ \Carbon\Carbon::parse($usage['period_end'])->diffForHumans() }})
                @endif
            </small>
        </div>
        @endforeach
        @else
        <p class="text-muted">No active limits apply to your account.</p>
        @endif

        @if(count($userUsage) > 0)
        <div class="mt-3">
            <small class="text-muted">
                <i class="las la-info-circle"></i>
                Limits reset automatically at the start of each period.
            </small>
        </div>
        @endif
    </div>
</div>
