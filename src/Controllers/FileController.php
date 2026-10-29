<?php

namespace BehinFileControl\Controllers;

use App\Http\Controllers\Controller;
use BehinFileControl\Models\FileUpload;
use BehinFileControl\Controllers\FileUploadSettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public static function store($file, $dir = 'docs', $ftp = false)
    {
        // دریافت تنظیمات از دیتابیس
        $settings = FileUploadSettingController::getCurrentSettings();

        // اگر تنظیمات FTP فعال است و صریحاً غیرفعال نشده است، از FTP استفاده می‌کنیم
        $useFtp = $ftp || $settings['ftp_enabled'];

        if ($useFtp) {
            if (!in_array($file->getMimeType(), config('file_control.valid_file_type'))) {
                return [
                    'status' => 400,
                    'message' => trans("File Format Is Invalid")
                ];
            }

            $ftpHost = $settings['ftp_host'];
            $ftpUrl = $settings['ftp_url'];
            $ftpUser = $settings['ftp_username'];
            $ftpPass = $settings['ftp_password'];

            if (!$ftpHost || !$ftpUser || !$ftpPass) {
                return [
                    'status' => 500,
                    'message' => trans("تنظیمات FTP ناقص است. لطفاً ابتدا تنظیمات FTP را ذخیره کنید")
                ];
            }

            $conn = ftp_connect($ftpHost);
            if (!$conn) return [
                'status' => 500,
                'message' => trans("Cann't connect to ftp server")
            ];;

            if (!ftp_login($conn, $ftpUser, $ftpPass)) return [
                'status' => 500,
                'message' => trans("Ftp login failed")
            ];;

            ftp_pasv($conn, true);

            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();

            $remotePath = "/public_html/crm/uploads/$filename";

            $uploaded = ftp_put($conn, $remotePath, $file->getRealPath(), FTP_BINARY);

            ftp_close($conn);

            if (!$uploaded) {
                return [
                    'status' => 500,
                    'message' => trans("Error In Uploading File")
                ];
                Log::alert("Error In Uploading File");
            }

            $downloadUrl = "https://$ftpUrl/crm/uploads/$filename";
            $guid = (string) Str::uuid();

            $fileUpload = self::saveFileRecord([
                'original_name' => $file->getClientOriginalName(),
                'file_name' => $filename,
                'guid' => $guid,
                'file_path' => $downloadUrl,
                'download_url' => route('file.download', ['guid' => $guid]),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'is_ftp' => true,
            ]);

            return [
                'status' => 200,
                'message' => trans("File Uploaded"),
                'dir' => $downloadUrl,
                'guid' => $guid,
                'file_id' => $fileUpload?->id,
                'download_url' => route('file.download', ['guid' => $guid]),
                'original_name' => $file->getClientOriginalName(),
                'user_id' => $fileUpload?->user_id,
                'user_name' => $fileUpload?->user_name,
            ];
        } else {
            $name = Str::random(40) . '.' . $file->getClientOriginalExtension();

            $full_path = public_path($dir);
            if (!is_dir($full_path)) {
                mkdir($full_path);
            }
            $full_name = $full_path . '/' . $name;
            $result = move_uploaded_file($file->getRealPath(), $full_name);
            if ($result) {
                $guid = (string) Str::uuid();
                $fileUrl = url($dir . '/' . $name);

                $fileUpload = self::saveFileRecord([
                    'original_name' => $file->getClientOriginalName(),
                    'file_name' => $name,
                    'guid' => $guid,
                    'file_path' => $dir . '/' . $name,
                    'download_url' => route('file.download', ['guid' => $guid]),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'is_ftp' => false,
                ]);

                return [
                    'status' => 200,
                    'message' => trans("File Uploaded"),
                    'dir' => $dir . '/' . $name,
                    'guid' => $guid,
                    'file_id' => $fileUpload?->id,
                    'download_url' => route('file.download', ['guid' => $guid]),
                    'original_name' => $file->getClientOriginalName(),
                    'user_id' => $fileUpload?->user_id,
                    'user_name' => $fileUpload?->user_name,
                ];
            }
            return [
                'status' => 500,
                'message' => trans("Error In Uploading File")
            ];
        }
    }

    /**
     * آپلود فایل از طریق درخواست HTTP (برای استفاده پکیج‌های دیگر)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $maxSize = config('file_control.max_file_size', 10240); // كيلو بایت

        $request->validate([
            'file' => 'required|file|max:' . $maxSize,
        ]);

        $file = $request->file('file');

        // بررسی نوع فایل مجاز
        $validTypes = config('file_control.valid_file_type', []);
        if (!empty($validTypes) && !in_array($file->getMimeType(), $validTypes)) {
            return response()->json([
                'error' => 'نوع فایل مجاز نیست',
            ], 400);
        }

        // استفاده از متد store برای ذخیره فایل و ثبت رکورد در دیتابیس
        $result = self::store($file, 'docs', false);

        if (isset($result['status']) && $result['status'] !== 200) {
            return response()->json([
                'error' => $result['message'] ?? 'خطا در آپلود فایل',
            ], $result['status']);
        }

        return response()->json([
            'url' => $result['download_url'],
            'file_id' => $result['file_id'],
            'guid' => $result['guid'],
            'original_name' => $result['original_name'],
        ]);
    }

    /**
     * دانلود یا نمایش فایل بر اساس GUID
     *
     * @param string $guid
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public static function download(string $guid)
    {
        $fileRecord = FileUpload::where('guid', $guid)->first();

        if (!$fileRecord) {
            abort(404, trans('File not found'));
        }

        // اگر فایل از طریق FTP ذخیره شده است، به لینک FTP ریدایرکت می‌کنیم
        if ($fileRecord->is_ftp && $fileRecord->download_url) {
            return redirect($fileRecord->download_url);
        }

        // برای فایل‌های محلی، فایل را از مسیر ذخیره شده برمی‌گردانیم
        $filePath = public_path($fileRecord->file_path);

        if (!file_exists($filePath)) {
            abort(404, trans('File not found on server'));
        }

        return response()->download(
            $filePath,
            $fileRecord->original_name,
            [
                'Content-Type' => $fileRecord->mime_type ?: 'application/octet-stream',
            ]
        );
    }

    /**
     * ذخیره مشخصات فایل در دیتابیس
     *
     * @param array $data
     * @return FileUpload|null
     */
    protected static function saveFileRecord(array $data): ?FileUpload
    {
        try {
            $userId = null;
            $userName = null;

            if (Auth::check()) {
                $user = Auth::user();
                $userId = $user->id ?? null;
                $userName = $user->name ?? ($user->username ?? null);
            }

            return FileUpload::create(array_merge($data, [
                'user_id' => $userId,
                'user_name' => $userName,
            ]));
        } catch (\Exception $e) {
            Log::error('FileUpload save error: ' . $e->getMessage());
            return null;
        }
    }
}
