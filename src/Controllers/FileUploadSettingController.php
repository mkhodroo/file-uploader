<?php

namespace BehinFileControl\Controllers;

use App\Http\Controllers\Controller;
use BehinFileControl\Models\FileUploadSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FileUploadSettingController extends Controller
{
    /**
     * نمایش صفحه تنظیمات آپلود فایل
     */
    public function index()
    {
        $companyId = optional(Auth::user())->company_id ?? null;
        $setting = FileUploadSetting::getForCompany($companyId);

        return view('file-upload::settings', [
            'setting' => $setting,
            'companyId' => $companyId,
        ]);
    }

    /**
     * ذخیره یا بروزرسانی تنظیمات آپلود فایل
     */
    public function store(Request $request)
    {
        $request->validate([
            'ftp_enabled' => 'nullable|boolean',
            'ftp_host' => 'nullable|string|max:255',
            'ftp_url' => 'nullable|string|max:255',
            'ftp_username' => 'nullable|string|max:255',
            'ftp_password' => 'nullable|string|max:255',
        ]);

        $companyId = $request->input('company_id', optional(Auth::user())->company_id ?? null);

        $setting = FileUploadSetting::getForCompany($companyId);

        if (!$setting) {
            $setting = new FileUploadSetting();
            $setting->company_id = $companyId;
        }

        $setting->ftp_enabled = $request->boolean('ftp_enabled', false);
        $setting->ftp_host = $request->input('ftp_host');
        $setting->ftp_url = $request->input('ftp_url');
        $setting->ftp_username = $request->input('ftp_username');
        $setting->ftp_password = $request->input('ftp_password');
        $setting->save();

        return redirect()
            ->back()
            ->with('success', trans('تنظیمات با موفقیت ذخیره شد'));
    }

    /**
     * تست اتصال به سرور FTP
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'ftp_host' => 'required|string|max:255',
            'ftp_username' => 'required|string|max:255',
            'ftp_password' => 'required|string|max:255',
        ]);

        $ftpHost = $request->input('ftp_host');
        $ftpUser = $request->input('ftp_username');
        $ftpPass = $request->input('ftp_password');

        $conn = @ftp_connect($ftpHost);
        if (!$conn) {
            return response()->json([
                'success' => false,
                'message' => trans('اتصال به سرور FTP برقرار نشد'),
            ], 400);
        }

        $loginResult = @ftp_login($conn, $ftpUser, $ftpPass);
        @ftp_close($conn);

        if (!$loginResult) {
            return response()->json([
                'success' => false,
                'message' => trans('نام کاربری یا رمز عبور FTP اشتباه است'),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => trans('اتصال به سرور FTP با موفقیت برقرار شد'),
        ]);
    }

    /**
     * دریافت تنظیمات فعلی (برای استفاده در API)
     *
     * @param int|null $companyId
     * @return array
     */
    public static function getCurrentSettings(?int $companyId = null): array
    {
        if (!$companyId && Auth::check()) {
            $companyId = optional(Auth::user())->company_id ?? null;
        }

        $setting = FileUploadSetting::getForCompany($companyId);

        if (!$setting) {
            return [
                'ftp_enabled' => false,
                'ftp_host' => null,
                'ftp_url' => null,
                'ftp_username' => null,
                'ftp_password' => null,
            ];
        }

        return [
            'ftp_enabled' => $setting->ftp_enabled,
            'ftp_host' => $setting->ftp_host,
            'ftp_url' => $setting->ftp_url,
            'ftp_username' => $setting->ftp_username,
            'ftp_password' => $setting->ftp_password,
        ];
    }
}