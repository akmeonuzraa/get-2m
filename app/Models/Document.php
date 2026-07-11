<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'description', 'file_path', 'original_filename',
        'file_type', 'mime_type', 'file_size', 'keywords',
        'service', 'folder_id', 'space_id', 'uploaded_by',
        'current_version', 'status', 'trashed_at',
    ];

    protected $casts = [
        'keywords' => 'array',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class);
    }
}