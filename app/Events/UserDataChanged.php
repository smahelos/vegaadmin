<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserDataChanged
{
    use Dispatchable, SerializesModels;

    /**
     * The user whose data has changed
     *
     * @var User
     */
    public $user;

    /**
     * The type of change that occurred
     *
     * @var string
     */
    public $changeType;

    /**
     * Create a new event instance
     *
     * @param User $user
     * @param string $changeType
     */
    public function __construct(User $user, string $changeType = 'general')
    {
        $this->user = $user;
        $this->changeType = $changeType;
    }
}
