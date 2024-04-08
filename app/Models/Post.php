<?php
namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Post extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'title', 'description'
    ];    
    public function postImages()
    {
        return $this->hasMany(PostImage::class);
    }

    public function postCommunity()
    {
        return $this->hasMany(PostCommunity::class);
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->with('replies');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commentcount()
    {
        return $this->hasMany(Comment::class);
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'post_like', 'post_id', 'liked_by');
    }

    public function deleteComments()
    {
        return $this->hasMany(Comment::class);
    }
}