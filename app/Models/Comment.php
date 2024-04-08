<?php
namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'comments';
    protected $fillable = ['content', 'parent_id', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')->with('user');
    }

    // public function parent()
    // {
    //     return $this->belongsTo(Comment::class, 'parent_id');
    // }
}
