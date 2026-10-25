<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Analytics\DTO\StatisticsFilter;
use App\Domain\Analytics\Enums\StatisticsPeriod;
use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StatisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->can('frontend.api.statistics') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'time_range' => ['nullable', Rule::enum(StatisticsPeriod::class)],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
            'clients' => ['nullable', 'array'],
            'clients.*' => [
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'time_range' => __('dashboard.filters.date_range'),
            'date_from' => __('dashboard.filters.date_from'),
            'date_to' => __('dashboard.filters.date_to'),
            'clients' => __('dashboard.filters.clients'),
            'clients.*' => __('dashboard.filters.clients'),
        ];
    }

    public function userId(): UserId
    {
        return UserId::fromInt($this->user('web')->id);
    }

    public function filter(): StatisticsFilter
    {
        $period = StatisticsPeriod::tryFrom((string) $this->validated('time_range')) ?? StatisticsPeriod::YEAR;
        $clientIds = array_map(
            static fn (int|string $id): PartyId => PartyId::fromInt((int) $id),
            $this->validated('clients') ?? [],
        );

        return new StatisticsFilter(
            period: $period,
            from: $this->validatedDate('date_from'),
            to: $this->validatedDate('date_to'),
            clientIds: array_values($clientIds),
        );
    }

    private function validatedDate(string $key): ?DateTimeImmutable
    {
        $value = $this->validated($key);
        if ($value === null) {
            return null;
        }

        return DateTimeImmutable::createFromFormat('!Y-m-d', $value) ?: null;
    }
}
