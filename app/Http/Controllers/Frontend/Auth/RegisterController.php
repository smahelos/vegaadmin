<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * The registration page; the Livewire RegistrationForm on it validates and registers.
 */
final class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }
}
