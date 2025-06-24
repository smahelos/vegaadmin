<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FormDataChanged
{
    use Dispatchable, SerializesModels;

    /**
     * The type of form data that changed
     *
     * @var string
     */
    public $dataType;

    /**
     * Create a new event instance
     *
     * @param string $dataType
     */
    public function __construct(string $dataType = 'general')
    {
        $this->dataType = $dataType;
    }
}
