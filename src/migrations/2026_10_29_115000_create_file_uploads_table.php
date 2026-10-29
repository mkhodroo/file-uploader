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
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('original_name')->comment('نام اصلی فایل هنگام آپلود');
            $table->string('file_name')->comment('نام فایل ذخیره شده روی سرور');
            $table->string('guid')->unique()->comment('شناسه منحصر به فرد فایل برای دسترسی');
            $table->unsignedBigInteger('user_id')->nullable()->comment('کد کاربری که فایل را آپلود کرده');
            $table->string('user_name')->nullable()->comment('نام کاربری که فایل را آپلود کرده');
            $table->string('file_path')->comment('مسیر ذخیره‌سازی فایل');
            $table->string('download_url')->nullable()->comment('لینک دسترسی به فایل با استفاده از GUID');
            $table->string('mime_type')->nullable()->comment('نوع FMIME فایل');
            $table->bigInteger('file_size')->nullable()->comment('حجم فایل به بایت');
            $table->boolean('is_ftp')->default(false)->comment('آیا فایل از طریق FTP آپلود شده است');
            $table->timestamps();

            $table->index('guid');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
    }
};