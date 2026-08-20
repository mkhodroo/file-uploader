<?php

namespace BehinFileControl\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public static function store($file, $dir = 'docs', $ftp = true)
    {
        if ($ftp) {
            if (!in_array($file->getMimeType(), config('file_control.valid_file_type'))) {
                return [
                    'status' => 400,
                    'message' => trans("File Format Is Invalid")
                ];
            }
            $ftpHost = '3117204815.cloudydl.com';
            $ftpUrl = 'dl.opticpardaz.com';
            $ftpUser = env('FTP_USERNAME');
            $ftpPass = env('FTP_PASSWORD');

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
            return [
                'status' => 200,
                'message' => trans("File Uploaded"),
                'dir' => $downloadUrl
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
                return [
                    'status' => 200,
                    'message' => trans("File Uploaded"),
                    'dir' => $dir . '/' . $name
                ];
            }
            return [
                'status' => 500,
                'message' => trans("Error In Uploading File")
            ];
        }
    }
}
