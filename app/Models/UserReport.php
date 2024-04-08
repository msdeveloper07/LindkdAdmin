<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserReport extends Model
{
    protected $table = 'user_reports';
    protected $fillable = [ 'reported_user', 'reported_by', 'description' ,'blocked_community'];

}