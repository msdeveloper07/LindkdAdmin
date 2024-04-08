<?php
namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PostImage extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'post_id', 'created_by', 'url', 'type', 'image'
    ];   
     
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}