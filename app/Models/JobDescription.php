<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobDescription extends Model
{
    use HasFactory, SoftDeletes;
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
    
    public function hardDelete()
    {
        return parent::delete();
    }
}
