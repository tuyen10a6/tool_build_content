<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_theme' => ['required', 'in:dark,light'],
        ]);

        AppSetting::putValue('app_theme', $validated['app_theme']);

        return back()->with('status', 'Đã cập nhật giao diện toàn hệ thống.');
    }
}
