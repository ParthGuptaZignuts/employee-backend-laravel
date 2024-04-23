<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;
    protected $table = 'jobs_applications';
    protected $fillable = ['user_id','company_id', 'job_descriptions_id', 'status' , 'resume'];

    public function user()
    {
        return $this->belongsTo(User::class); 
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function jobDescription()
    {
        return $this->belongsTo(JobDescription::class); 
    }


}
