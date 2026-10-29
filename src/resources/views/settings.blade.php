<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنظیمات آپلود فایل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .setting-card { max-width: 700px; margin: 40px auto; }
    </style>
</head>
<body>
<div class="container">
    <div class="setting-card card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">تنظیمات ذخیره‌سازی فایل</h5>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('file-upload-settings.store') }}" method="POST">
                @csrf
                <input type="hidden" name="company_id" value="{{ $companyId ?? '' }}">
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" name="ftp_enabled" id="ftp_enabled" value="1" {{ old('ftp_enabled', optional($setting)->ftp_enabled) ? 'checked' : '' }}>
                    <label class="form-check-label" for="ftp_enabled">
                        ذخیره‌سازی فایل‌ها روی سرور FTP (اگر غیرفعال باشد، در پوشه محلی ذخیره می‌شود)
                    </label>
                </div>
                <div id="ftp_fields" class="{{ old('ftp_enabled', optional($setting)->ftp_enabled) ? '' : 'd-none' }}">
                    <div class="mb-3"><label class="form-label">هاست FTP</label><input type="text" name="ftp_host" class="form-control" value="{{ old('ftp_host', optional($setting)->ftp_host) }}" placeholder="مثال: ftp.example.com"></div>
                    <div class="mb-3"><label class="form-label">آدرس دانلود FTP (URL)</label><input type="text" name="ftp_url" class="form-control" value="{{ old('ftp_url', optional($setting)->ftp_url) }}" placeholder="مثال: https://files.example.com"><div class="form-text">آدرسی که کاربر با آن به فایل دسترسی داشته باشد</div></div>
                    <div class="mb-3"><label class="form-label">نام کاربری FTP</label><input type="text" name="ftp_username" class="form-control" value="{{ old('ftp_username', optional($setting)->ftp_username) }}"></div>
                    <div class="mb-3"><label class="form-label">رمز عبور FTP</label><input type="password" name="ftp_password" class="form-control" value="{{ old('ftp_password', optional($setting)->ftp_password) }}"></div>
                    <div class="mb-3 text-end"><button type="button" class="btn btn-outline-primary btn-sm" id="test_ftp_connection">تست اتصال به FTP</button><div id="test_result" class="mt-2"></div></div>
                </div>
                <div class="d-grid gap-2 mt-4"><button type="submit" class="btn btn-primary">ذخیره تنظیمات</button><a href="{{ url('/') }}" class="btn btn-outline-secondary">بازگشت</a></div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const ftpCheckbox = document.getElementById('ftp_enabled');
    const ftpFields = document.getElementById('ftp_fields');
    ftpCheckbox.addEventListener('change', function() { if (this.checked) { ftpFields.classList.remove('d-none'); } else { ftpFields.classList.add('d-none'); } });
    document.getElementById('test_ftp_connection').addEventListener('click', function() {
        const btn = this; const resultDiv = document.getElementById('test_result');
        const host = document.querySelector('input[name="ftp_host"]').value;
        const username = document.querySelector('input[name="ftp_username"]').value;
        const password = document.querySelector('input[name="ftp_password"]').value;
        btn.disabled = true; btn.innerHTML = 'در حال تست...'; resultDiv.innerHTML = '';
        fetch('{{ route('file-upload-settings.test') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ ftp_host: host, ftp_username: username, ftp_password: password })
        }).then(response => response.json()).then(data => {
            btn.disabled = false; btn.innerHTML = 'تست اتصال به FTP';
            resultDiv.innerHTML = '<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>';
        }).catch(error => { btn.disabled = false; btn.innerHTML = 'تست اتصال به FTP'; resultDiv.innerHTML = '<div class="alert alert-danger">خطای نامشخص رخ داد</div>'; });
    });
</script>
</body>
</html>