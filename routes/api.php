<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommunityContoller;
use App\Http\Controllers\UserContoller;
use App\Http\Controllers\EndUserContoller;
use App\Http\Controllers\StoryController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::fallback(function () {
    return response()->json(['error' => 'Unauthenticated.'], 401);
})->name('login');

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('verify-user', [AuthController::class, 'verifyUser']);
Route::post('social-login', [AuthController::class, 'socialLogin']);
Route::post('check-social-user', [AuthController::class, 'socialUserExit']);
Route::post('forgot-password-request', [AuthController::class, 'forgotPasswordRequest']);
Route::post('verify-forgot-password', [AuthController::class, 'verifyForgotPasswordCode']);
Route::post('update-password', [AuthController::class, 'updateUserPassword']);
Route::post('resend-otp', [AuthController::class, 'resendOtp']);
Route::get('send-test-mail', [AuthController::class, 'sendEmail']);
Route::post('user-posts', [PostController::class, 'userposts']);
Route::get('quote', [UserContoller::class, 'quote']);
Route::post('/get-user-detail', [UserContoller::class, 'getUsersDetail']);



Route::middleware('auth:api')->group(function () {
    /*** Posts ***/
    Route::resource('posts', PostController::class);
    Route::post('user-posts', [PostController::class, 'userposts']);
    Route::post('post-like', [PostController::class, 'postLike']);
    Route::post('post-dislike', [PostController::class, 'postDisLike']);
    Route::post('single-post', [PostController::class, 'getSinglePost']);
    Route::get('post-liked-user', [PostController::class, 'getPostLikedUsers']);
    Route::get('post-commented-user', [PostController::class, 'getPostCommentedUsers']);
    Route::post('post-edit', [PostController::class, 'postUpdate']);
    Route::post('post-delete', [PostController::class, 'deletePost']);
    /*** Comment ***/
    Route::post('post-comment', [CommentController::class, 'addCommentToPost']);
    Route::get('get-comment', [CommentController::class, 'getComments']);
    /*** Community ***/
    Route::post('create-cummunity', [CommunityContoller::class, 'createCommunity']);
    Route::get('get-communities', [CommunityContoller::class, 'getCommunities']);
    /*** Users ***/
    Route::post('update-user-community', [UserContoller::class, 'updateUserCommunity']);
    Route::post('/logout', [UserContoller::class, 'logout']);
    Route::post('/update-user', [UserContoller::class, 'updateUserProfile']);
    Route::get('/user-media', [UserContoller::class, 'getUserMedia']);
    Route::get('/user-community', [UserContoller::class, 'getUserCommunity']);
    Route::post('/community-users', [UserContoller::class, 'getCommunityUsers']);
    Route::get('/user-detail', [UserContoller::class, 'getUserDetail']);
    Route::post('/update-LatLong', [UserContoller::class, 'updateLatLong']);
    Route::get('/map-users', [UserContoller::class, 'mapUsers']);


    /*** EndUser */
    Route::get('/end-user-media', [EndUserContoller::class, 'getEndUserMedia']);
    Route::get('/end-user-community', [EndUserContoller::class, 'getEndUserCommunity']);
    Route::post('/end-user-report', [EndUserContoller::class, 'userReportToAdmin']);
    Route::post('/user-block', [EndUserContoller::class, 'blockUser']);
    Route::post('/end-users', [EndUserContoller::class, 'gettingEndUsers']);
    /*** Stories ***/
    Route::get('get-stories', [StoryController::class, 'getStories']);
    Route::post('create-story', [StoryController::class, 'createStory']);
    Route::post('delete-story', [StoryController::class, 'deleteUserStory']);
    Route::post('read-story', [StoryController::class, 'readStoryByUser']);
});