<?php

namespace BehinFileControl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileUpload extends Model
{
    use HasFactory;

    protected $table = 'file_uploads';

    protected $fillable = [
        'original_name',
        'file_name',
        'guid',
        'user_id',
        'user_name',
        'file_path',
        'download_url',
        'mime_type',
        'file_size',
        'is_ftp',
    ];

    protected $casts = [
        'is_ftp' => 'boolean',
        'file_size' => 'integer',
    ];
}