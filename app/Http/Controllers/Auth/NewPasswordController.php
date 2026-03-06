<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $email = (string) $request->query('email', '');
        $token = (string) $request->route('token');
        $isSetupLink = $request->boolean('setup');

        if ($email === '' || $token === '') {
            return redirect()
                ->route('login')
                ->with('status', 'This set-password link is invalid or already used.');
        }

        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            return redirect()
                ->route('login')
                ->with('status', 'This set-password link is invalid or already used.');
        }

        if ($isSetupLink && method_exists($user, 'hasVerifiedEmail') && $user->hasVerifiedEmail()) {
            return redirect()
                ->route('login')
                ->with('status', 'Your password is already set. Please log in.');
        }

        if (!Password::broker()->tokenExists($user, $token)) {
            return redirect()
                ->route('login')
                ->with('status', 'This set-password link is invalid or already used.');
        }

        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                if (method_exists($user, 'hasVerifiedEmail') && !$user->hasVerifiedEmail()) {
                    $user->forceFill([
                        'email_verified_at' => now(),
                    ])->save();
                }

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', 'Password set successfully. You can now log in.')
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
