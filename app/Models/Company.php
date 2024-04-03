<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'location',
        'company_email',
        'status',
        'website',
        'logo_url',
        'status',
    ];
    
    public function admin()
    {
        return $this->belongsTo(User::class)->where('type', 'CA');
    }


    public function companyUsers()
    {
        return $this->hasMany(CompanyUser::class)->withPivot('joining_date','emp_number');
    }
}
