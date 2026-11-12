<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Domain\User\DTO\UserDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An account offered by the admin's owner lookup.
 */
final class UserOptionResource extends JsonResource
{
    public function __construct(private readonly UserDTO $user)
    {
        parent::__construct($user);
    }

    /** @return array<string, int|string|null> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
        ];
    }
}
