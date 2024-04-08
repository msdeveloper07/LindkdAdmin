<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'stories';
    protected $fillable = ['image', 'video', 'user_id', 'description', 'expires_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
