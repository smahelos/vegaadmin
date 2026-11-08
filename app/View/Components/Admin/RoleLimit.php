<?php

declare(strict_types=1);

namespace App\View\Components\Admin;

use App\Domain\User\Enums\LimitMode;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The mode select and number of one role limit (D-047), restored from the old input after a failed validation;
 * RoleLimitRequest turns the pair into the column value.
 */
final class RoleLimit extends Component
{
    public readonly string $modeName;

    public readonly LimitMode $mode;

    public readonly string $limit;

    /**
     * @param  array{name: string, label: string, value?: ?int, hint?: string}  $field  the Backpack field
     */
    public function __construct(
        public readonly array $field,
        bool $editing,
    ) {
        $this->modeName = $field['name'] . '_mode';
        $stored = LimitMode::LIMITED;
        if ($editing) {
            $stored = LimitMode::of($field['value'] ?? null);
        }
        $this->mode = LimitMode::tryFrom(
            (string) old($this->modeName, $stored->value),
        ) ?? $stored;
        $limit = '';
        if ($this->mode === LimitMode::LIMITED) {
            $limit = (string) old($field['name'], $field['value'] ?? '');
        }
        $this->limit = $limit;
    }

    public function render(): View
    {
        return view('components.admin.role-limit');
    }
}
