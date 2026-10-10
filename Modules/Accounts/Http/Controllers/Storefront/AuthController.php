<?php

namespace Modules\Accounts\Http\Controllers\Storefront;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Accounts\Events\CompanyRegistered;
use Modules\Accounts\Models\Company;

class AuthController extends Controller
{
    /**
     * Storefront Registration (Backend).
     *
     * Membuat company (npwp NULL bila perorangan) + user (company_role='admin')
     * dalam satu transaksi, lalu mendispatch event CompanyRegistered.
     */
    public function register(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'account_type' => ['nullable', 'string', Rule::in(['individual', 'company', 'perorangan', 'perusahaan'])],
            'company_name' => ['nullable', 'string', 'max:255'],
            'npwp'         => ['nullable', 'string', 'max:30', 'unique:companies,npwp'],
        ]);

        $isCompany = in_array($validated['account_type'] ?? '', ['company', 'perusahaan'], true)
            || ! empty($validated['company_name']);

        $companyName = $isCompany
            ? ($validated['company_name'] ?? $validated['name'])
            : $validated['name'];

        $npwp = $isCompany ? ($validated['npwp'] ?? null) : null;

        $user = DB::transaction(function () use ($validated, $companyName, $npwp) {
            $company = Company::create([
                'name'        => $companyName,
                'npwp'        => $npwp,
                'phone'       => $validated['phone'] ?? null,
                'email'       => $validated['email'],
                'is_verified' => false,
            ]);

            $user = User::create([
                'name'         => $validated['name'],
                'email'        => $validated['email'],
                'password'     => Hash::make($validated['password']),
                'phone'        => $validated['phone'] ?? null,
                'company_id'   => $company->id,
                'company_role' => 'admin',
            ]);

            event(new CompanyRegistered($company, $user));

            return $user;
        });

        Auth::login($user);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Registrasi berhasil.',
                'user'    => $user->load('company'),
            ], 201);
        }

        return redirect()->intended('/dashboard');
    }

    /**
     * Storefront Login.
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Login berhasil.',
                'user'    => Auth::user()->load('company'),
            ]);
        }

        return redirect()->intended('/dashboard');
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
            return response()->json(['message' => __($status)]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Reset Password: reset password menggunakan broker bawaan Laravel.
     */
    public function resetPassword(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                Auth::login($user);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)]);
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
