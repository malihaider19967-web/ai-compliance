<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    use HasFactory;

    protected $table = 'policies';

    protected $fillable = [
        'name',
        'rule_text',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
