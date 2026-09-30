<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'template_id',
        'form_data',
        'status',
        'pdf_path',
        'rejection_reason',
        'token',
        'requester_data',
    ];

    protected function casts(): array
    {
        return [
            'form_data' => 'array',
            'requester_data' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(LetterTemplate::class, 'template_id');
    }
}
