<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobDescription extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'title',
        'salary',
        'employment_type',
        'experience_required',
        'skills_required',
        'posted_date',
        'expiry_date',
    ];
}
