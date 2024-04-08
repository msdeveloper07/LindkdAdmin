<?php
namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class StoryRead extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'story_read';
    protected $fillable = [
        'story_id', 'read_by'
    ];   
}