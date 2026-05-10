<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant',
        'transaction_date',
        'total',
        'tax',
        'currency',
        'category',
        'payment_method',
        'line_items',
        'raw_extraction',
        'receipt_path',
        'policy_results',
        'status',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'total' => 'decimal:2',
        'tax' => 'decimal:2',
        'line_items' => 'array',
        'raw_extraction' => 'array',
        'policy_results' => 'array',
    ];
}
