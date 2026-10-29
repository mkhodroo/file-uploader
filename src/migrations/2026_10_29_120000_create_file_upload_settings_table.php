<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('file_upload_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index()->comment('کد شرکت (null برای تنظیمات پیش‌فرض allgemein)');
            $table->boolean('ftp_enabled')->default(false)->comment('آیا فایل‌ها روی FTP ذخیره شوند');
            $table->string('ftp_host')->nullable()->comment('هاست سرور FTP');
            $table->string('ftp_url')->nullable()->comment('آدرس دانلود فایل از FTP');
            $table->string('ftp_username')->nullable()->comment('نام کاربری FTP');
            $table->string('ftp_password')->nullable()->comment('رمز عبور FTP');
            $table->timestamps();

            $table->unique(['company_id', 'ftp_enabled'], 'unique_company_setting');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('file_upload_settings');
    }
};