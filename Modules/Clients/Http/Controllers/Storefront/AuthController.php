<?php

namespace Modules\Clients\Http\Controllers\Storefront;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Clients\Events\ClientRegistered;
use Modules\Clients\Models\Client;
use Modules\Email\Notifications\AccountRegisteredNotification;
use Modules\Loyalty\Services\LoyaltyService;

class AuthController extends Controller
{
    public function showLogin(): InertiaResponse
    {
        return Inertia::render('Catalog::LoginPage');
    }

    public function showRegister(): InertiaResponse
    {
        return Inertia::render('Catalog::RegisterPage');
    }

    public function showForgotPassword(): InertiaResponse
    {
        return Inertia::render('Catalog::ForgotPasswordPage');
    }

    public function showResetPassword(Request $request, string $token): InertiaResponse
    {
        return Inertia::render('Catalog::ForgotPasswordPage', [
            'resetToken' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    /**
     * Storefront Registration (Backend).
     *
     * Membuat company (npwp NULL bila perorangan) + user (company_role='admin')
     * dalam satu transaksi, lalu mendispatch event ClientRegistered.
     */
    public function register(Request $request, LoyaltyService $loyalty): JsonResponse|RedirectResponse
    {
        $request->attributes->set('pre_auth_session_id', $request->session()->getId());
        $request->merge([
            'referral_code' => $request->filled('referral_code')
                ? strtoupper(trim((string) $request->input('referral_code')))
                : null,
        ]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'account_type' => ['nullable', 'string', Rule::in(['individual', 'client', 'perorangan', 'perusahaan'])],
            'company_name' => ['nullable', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:30', 'unique:clients,npwp'],
            'referral_code' => ['nullable', 'string', 'max:20', 'exists:users,referral_code'],
        ]);

        $referrer = ! empty($validated['referral_code'])
            ? User::query()->where('referral_code', strtoupper(trim($validated['referral_code'])))->first()
            : null;

        $isClient = in_array($validated['account_type'] ?? '', ['client', 'perusahaan'], true)
            || ! empty($validated['company_name']);

        $companyName = $isClient
            ? ($validated['company_name'] ?? $validated['name'])
            : $validated['name'];

        $npwp = $isClient ? ($validated['npwp'] ?? null) : null;

        $user = DB::transaction(function () use ($validated, $companyName, $npwp, $referrer) {
            $company = Client::create([
                'name' => $companyName,
                'npwp' => $npwp,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'],
                'is_verified' => false,
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'client_id' => $company->id,
                'company_role' => 'admin',
                'referral_code' => 'REF'.random_int(100000, 999999),
                'referred_by_user_id' => $referrer?->id,
            ]);

            event(new ClientRegistered($company, $user));

            return $user;
        });

        $loyalty->awardForRegistration($user);

        Auth::login($user);
        Notification::route('mail', $user->email)
            ->notify((new AccountRegisteredNotification($user->name))->afterCommit());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Registrasi berhasil.',
                'user' => $user->load('client'),
            ], 201);
        }

        return redirect()->intended('/akun');
    }

    /**
     * Storefront Login.
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $request->attributes->set('pre_auth_session_id', $request->session()->getId());
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! Auth::user()?->client_id) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak dapat digunakan untuk akun storefront.',
            ]);
        }

        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Login berhasil.',
                'user' => Auth::user()->load('client'),
            ]);
        }

        return redirect()->intended('/akun');
    }

    /**
     * Storefront Logout.
     */
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Logout berhasil.']);
        }

        return redirect('/');
    }

    /**
     * Lupa Password: kirim link reset menggunakan broker bawaan Laravel.
     */
    public function forgotPassword(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return $request->wantsJson()
                ? response()->json(['message' => __($status)])
                : back()->with('success', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Reset Password: reset password menggunakan broker bawaan Laravel.
     */
    public function resetPassword(Request $request, string $token): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker()->reset(
            [...$request->only('email', 'password', 'password_confirmation'), 'token' => $token],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                Auth::login($user);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $request->wantsJson()
                ? response()->json(['message' => __($status)])
                : redirect()->route('login')->with('success', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Undangan Sub-Akun.
     *
     * TODO: Alur undangan sub-akun belum didesain (menunggu spesifikasi final).
     * Jangan buat implementasi sebelum ada PRD/spesifikasi resmi.
     */
}




