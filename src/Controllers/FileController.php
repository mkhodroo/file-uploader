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

            $fileUpload = self::saveFileRecord(
                originalName: $file->getClientOriginalName(),
                fileName: $filename,
                guid: $guid,
                filePath: $downloadUrl,
                downloadUrl: route('file.download', ['guid' => $guid]),
                mimeType: $file->getMimeType(),
                fileSize: $file->getSize(),
                isFtp: true,
            );

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
            $result = move_uploaded_file($file, $full_name);
            if ($result) {
                $guid = (string) Str::uuid();
                $fileUrl = url($dir . '/' . $name);

                $fileUpload = self::saveFileRecord(
                    originalName: $file->getClientOriginalName(),
                    fileName: $name,
                    guid: $guid,
                    filePath: $dir . '/' . $name,
                    downloadUrl: route('file.download', ['guid' => $guid]),
                    mimeType: $file->getMimeType(),
                    fileSize: $file->getSize(),
                    isFtp: false,
                );

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
