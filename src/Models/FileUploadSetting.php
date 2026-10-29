<?php

namespace BehinFileControl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileUploadSetting extends Model
{
    use HasFactory;

    protected $table = 'file_upload_settings';

    protected $fillable = [
        'company_id',
        'ftp_enabled',
        'ftp_host',
        'ftp_url',
        'ftp_username',
        'ftp_password',
    ];

    protected $casts = [
        'ftp_enabled' => 'boolean',
    ];

    /**
     * دریافت تنظیمات برای یک شرکت
     *
     * @param int|null $companyId
     * @return FileUploadSetting|null
     */
    public static function getForCompany(?int $companyId): ?self
    {
        if ($companyId) {
            $setting = self::where('company_id', $companyId)->first();
            if ($setting) {
                return $setting;
            }
        }

        return self::where('company_id', null)->first();
    }
}