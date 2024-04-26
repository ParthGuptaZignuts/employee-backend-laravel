<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory , SoftDeletes;
    protected $fillable = ['name', 'email', 'website', 'logo', 'address', 'status',];
    
    public function admin()
    {
        return $this->hasOne(User::class)->where('type', 'CA');
    }

    public function employees()
{
    return $this->hasMany(User::class)->where('type', 'E');
}

    public function jobDescriptions()
    {
        return $this->hasMany(JobDescription::class);
    }
    
    public function hardDelete()
    {
        return parent::delete();
    }
}
