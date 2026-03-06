<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();

    $user = Auth::user();

    // 🚫 Block inactive users
    if ($user->status !== 'active') {
        Auth::logout();

        return back()->withErrors([
            'email' => 'Your account is inactive. Please contact HR.',
        ]);
    }

    // ✅ Only active users continue
    $request->session()->regenerate();

    return match ($user->role) {
        'faculty'   => redirect()->route('faculty.dashboard'),
        'dean'      => redirect()->route('dean.dashboard'),
        'hr'        => redirect()->route('hr.dashboard'),
        'vpaa'      => redirect()->route('vpaa.dashboard'),
        'president' => redirect()->route('president.dashboard'),
        default     => redirect('/'),
    };
}

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
