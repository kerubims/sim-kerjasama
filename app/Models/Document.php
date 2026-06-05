<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'type',
        'document_number',
        'title',
        'content',
        'file_path',
        'start_date',
        'end_date',
        'status',
        'allow_client_upload',
        'created_by',
    ];

    public function parent()
    {
        return $this->belongsTo(Document::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Document::class, 'parent_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parties()
    {
        return $this->hasMany(DocumentParty::class);
    }

    public function histories()
    {
        return $this->hasMany(DocumentHistory::class);
    }

    public function comments()
    {
        return $this->hasMany(DocumentComment::class);
    }
}
