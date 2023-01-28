<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Role;
use App\Models\Info;
use App\Models\Social;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use \DateTimeInterface;

class Roleuser extends Model
{
    use HasFactory;
    
        /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user',
        'role',
    ];
    
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    
    
    public function infos()
    {
       // return $this->belongsTo(Info::class, 'user');
        return $this->hasOne(Info::class, 'user', 'user');
    }
    
    public function social()
    {
        //return $this->belongsTo(Social::class, 'user');
        return $this->hasOne(Social::class, 'user', 'user');
        
    }
}