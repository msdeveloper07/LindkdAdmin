<?php
namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PostCommunity extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'post_community';
    protected $fillable = [
        'post_id', 'created_by', 'community_id'
    ];   

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}