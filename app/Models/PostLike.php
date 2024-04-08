<?php
namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PostLike extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'post_like';
    protected $fillable = [
        'post_id', 'liked_by'
    ];   

    // Relationship with Post model
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}