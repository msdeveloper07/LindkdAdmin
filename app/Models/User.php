<?php
namespace App\Models;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'otp',
        'provider',
        'facebook_id',
        'google_id',
        'profile_image',
        'latitude',
        'longitude'
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function likedPosts()
    {
        return $this->belongsToMany(Post::class, 'post_like', 'liked_by', 'post_id');
    }

    public function userMedai()
    {
        return $this->hasMany(PostImage::class, 'created_by');
    }

    public function userCommunities()
    {
        return $this->hasMany(UserCommunity::class);
    }

    public function blockedUsers()
    {
        return $this->hasMany(UserBlock::class, 'blocked_by');
    }
}