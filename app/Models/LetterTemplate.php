<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'content',
        'required_fields',
    ];

    protected function casts(): array
    {
        return [
            'required_fields' => 'array',
        ];
    }

    public function letterRequests()
    {
        return $this->hasMany(LetterRequest::class, 'template_id');
    }
}
