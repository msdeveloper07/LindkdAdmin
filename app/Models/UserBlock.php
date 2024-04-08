<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserBlock extends Model
{
    protected $table = 'user_block';
    protected $fillable = [ 'blocked_by', 'blocked_user','blocked_community'];

}