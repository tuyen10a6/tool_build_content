<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()->orderBy('created_at')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        $password = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            ...$validated,
            'name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => $password['password'],
        ]);

        return redirect()->route('users.index')->with('status', 'Tạo tài khoản thành công.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validatePayload($request, $user);

        $user->update([
            ...$validated,
            'name' => $validated['full_name'],
        ]);

        return redirect()->route('users.index')->with('status', 'Cập nhật tài khoản thành công.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'locked'])],
        ]);

        $user->update(['status' => $validated['status']]);

        return redirect()->route('users.index')->with('status', 'Cập nhật trạng thái tài khoản thành công.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('users.index')->with('status', 'Đặt lại mật khẩu thành công.');
    }

    private function validatePayload(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
            'role' => ['required', Rule::in(['admin', 'reviewer', 'user'])],
            'status' => ['required', Rule::in(['active', 'locked'])],
        ]);
    }
}
