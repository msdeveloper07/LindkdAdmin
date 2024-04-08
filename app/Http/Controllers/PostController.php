<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\PostCommunity;
use App\Models\PostLike;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostController extends Controller
{
    /*** Post Listing API ***/
    public function index()
    {
        $user_id = Auth::id();
        $blockedUserIds = Auth::user()->blockedUsers()->pluck('blocked_user');
        $posts = Post::with('postImages')->with('postCommunity')->with('comments')->with('user')->orderBy('id', 'desc')->get();
        foreach ($posts as $key => $post) {
            $post_like = Post::withCount('likes')->find($post->id);
            $post->post_like_count = $post_like->likes_count;
            $post_comment = Post::withCount('commentcount')->find($post->id);
            $post->post_comment_count = $post_comment->commentcount_count;
            $post_like = PostLike::where('post_id',$post->id)->where('liked_by',$user_id)->first();
            if(!empty($post_like)){
                $post->likedbyme = 'true';
            }else{
                $post->likedbyme = 'false';
            }
        }
        return response()->json([ 'type' => 'success', 'message' => 'Post getting successfully', 'data' => $posts]);
    }

    /*** Save Post API ***/
    public function store(Request $request)
    {  
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            //'image.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240', // Maximum file size of 10MB
            'community_id' => 'required',
        ]);
        
        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            } 
        }
        try{
            $user_id = Auth::id();
            $post = new Post();
            $post->title = $request->title;
            $post->description = $request->description;
            $post->created_by = $user_id;
            if (auth()->user()->posts()->save($post)){
                $post = $post->toArray();
                //Post Community Code
                $community_ids = $request->community_id;
                if(count($community_ids)>0){
                    foreach ($community_ids as $key => $community_id) {
                        $post_community = new PostCommunity();
                        $post_community->community_id = $community_id;
                        $post_community->post_id = $post['id'];
                        $post_community->created_by = $user_id;
                        $post_community->save();
                    }
                }
                //Post Image Code
                // if ($request->hasFile('image')) {
                //     $uploadedImages = [];
                //     foreach ($request->file('image') as $image) {
                //         $name = time().'.'.$image->getClientOriginalExtension();
                //         $destinationPath = public_path('/post_images');
                //         $image->move($destinationPath, $name);
                //         // Save data into post_images table
                //         $post_image = PostImage::create([
                //             'post_id' => $post['id'],
                //             'created_by' => $user_id,
                //             'type' => 'image',
                //             'url' => '/public/post_images/'.$name
                //         ]);
                //     }
                // }
                //Post Videos
                // if (!empty($request->video_url)) {
                //     $videos = $request->video_url;
                //     foreach ($videos as $video) {
                //         $post_image = PostImage::create([
                //             'post_id' => $post['id'],
                //             'created_by' => $user_id,
                //             'type' => 'video',
                //             'url' => $video
                //         ]);
                //     }
                // }

                // Using S3 Links for Images and Video
                $media_decode = json_decode($request->media, true);
                foreach ($media_decode as $index => $item) {
                    $thumbnail = $item['thumbnail'];
                    $video = $item['video'];
                    $type = $item['type'];
                    $post_image = PostImage::create([
                        'post_id' => $post['id'],
                        'created_by' => $user_id,
                        'type' => $type,
                        'image' => $thumbnail,
                        'url' => $video
                    ]);
                }
                return response()->json([ 'type' => 'success', 'message' => 'Post successfully created', 'data' => $post ]);
            }else{
                return response()->json([ 'type' => 'error', 'message' => 'Post not added' ], 401);
            }
        } catch (ModelNotFoundException $exception) {
            return response()->json([ 'type' => 'error', 'message' => 'Something went wrong, Please try again!'],401);
        }
    }
    
    /*** Delete Post API ***/
    public function deletePost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }           
        }
        try{
            $id = $request->post_id;
            $post = Post::findOrFail($id);
            if (!$post) {
                return response()->json([ 'type' => 'error', 'message' => 'Post not found'], 401);
            }
            //$post = Post::findOrFail($id);
            $post->postImages()->delete(); // Delete associated images
            $post->deleteComments()->delete(); // Delete associated comments
            $post->postCommunity()->delete(); // Delete associated Community
            $post->likes()->delete(); // Delete Post Likes
            $post->delete();
            if ($post) {
                return response()->json([ 'type' => 'success', 'message' => 'Post delete successfully'], 200);
            } else {
                return response()->json(['type' => 'error', 'message' => 'Post can not be deleted'], 401);
            }
        } catch (ModelNotFoundException $exception) {
            return response()->json([ 'type' => 'error', 'message' => 'Something went wrong, Please try again!'],401);
        }
    }

    /*** Post Like ***/
    public function postLike(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }           
        }
        try{
            $user_id = Auth::id();
            $id = $request->post_id;
            $post = Post::findOrFail($id);
            if (!$post) {
                return response()->json([ 'type' => 'error', 'message' => 'Post not found'], 401);
            }

            $liked = PostLike::where('post_id',$id)->where('liked_by',$user_id)->get();
            if(count($liked)>0){
                return response()->json([ 'type' => 'error', 'message' => 'You already liked this post.'], 401);
            }
            
            $postlike = new PostLike();
            $postlike->post_id = $id;
            $postlike->liked_by = $user_id;
            $postlike->save();

            return response()->json([ 'type' => 'success', 'message' => 'Post Liked Successfully.', 'data' => $post], 200);
        } catch (ModelNotFoundException $exception) {
            return response()->json([ 'type' => 'error', 'message' => 'Something went wrong, Please try again!'],401);
        }
    }

    /*** Post DisLike ***/
    public function postDisLike(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }           
        }
        try{
            $user_id = Auth::id();
            $id = $request->post_id;
            $post = Post::findOrFail($id);
            if (!$post) {
                return response()->json([ 'type' => 'error', 'message' => 'Post not found'], 401);
            }

            $liked = PostLike::where('post_id',$id)->where('liked_by',$user_id)->get();
            if(count($liked) == 0){
                return response()->json([ 'type' => 'error', 'message' => 'You did not liked this post.'], 401);
            }
            
            $postlike = PostLike::where('post_id',$id)->where('liked_by',$user_id)->delete();

            return response()->json([ 'type' => 'success', 'message' => 'Post Dislike Successfully.', 'data' => $post], 200);
        } catch (ModelNotFoundException $exception) {
            return response()->json([ 'type' => 'error', 'message' => 'Something went wrong, Please try again!'],401);
        }
    }

    /*** API Getting Single Post Detail ***/
    public function getSinglePost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }           
        }
        try{
            $id = $request->post_id;
            $post = Post::findOrFail($id);
            if (!$post) {
                return response()->json([ 'type' => 'error', 'message' => 'Post not found'], 401);
            }
            $posts = Post::where('id',$id)->with('postImages')->with('postCommunity')->with('comments')->with('user')->get();
            foreach ($posts as $key => $post) {
                $post_like = Post::withCount('likes')->find($post->id);
                $post->post_like_count = $post_like->likes_count;

                $post_comment = Post::withCount('commentcount')->find($post->id);
                $post->post_comment_count = $post_comment->commentcount_count;
            }

            return response()->json([ 'type' => 'success', 'message' => 'Post getting successfully', 'data' => $posts], 200);
        } catch (ModelNotFoundException $exception) {
            return response()->json([ 'type' => 'error', 'message' => 'Something went wrong, Please try again!'],401);
        }
    }

    /*** API Getting User Who Liked Post***/
    public function getPostLikedUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }           
        }
        try {
            $id = $request->post_id;
            $post = Post::findOrFail($id);
            if (!$post) {
                return response()->json([ 'type' => 'error', 'message' => 'Post not found'], 401);
            }

            $post = Post::find($id);
            $likedUsers = $post->likedByUsers; // Get the users who liked the post
            $users = array();
            foreach ($likedUsers as $user) {
                $data['id'] = $user->id;
                $data['name'] = $user->name;
                $data['email'] = $user->email;
                $data['profile_image'] = $user->profile_image;
                $users[] = $data;
            }
            return response()->json([ 'type' => 'success', 'message' => 'User getting successfully', 'data' => $users]);
        } catch (ModelNotFoundException $exception) {
            return response()->json([ 'type' => 'error', 'message' => 'Something went wrong, Please try again!'],401);
        }
    }

    /*** API Getting User Who Commented On Post***/
    public function getPostCommentedUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }           
        }
        try{
            $id = $request->post_id;
            $post = Post::findOrFail($id);
            if (!$post) {
                return response()->json([ 'type' => 'error', 'message' => 'Post not found'], 401);
            }

            $post = Post::find($id);
            $comments = $post->comments()->with('user')->get();; // Get the users who liked the post
            $users = array();
            foreach ($comments as $comment) {
                $data['id'] = $comment->id;
                $data['name'] = $comment->name;
                $data['email'] = $comment->email;
                $data['profile_image'] = $comment->profile_image;
                $users[] = $data;
            }
            return response()->json([ 'type' => 'success', 'message' => 'User getting successfully', 'data' => $comments], 200);
        } catch (ModelNotFoundException $exception) {
            return response()->json([ 'type' => 'error', 'message' => 'Something went wrong, Please try again!'],401);
        }
    }

    /*** API Update Post (Only Description updated)***/
    public function postUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }           
        }
        try{
            $id = $request->post_id;
            $post = Post::findOrFail($id);
            if (!$post) {
                return response()->json([ 'type' => 'error', 'message' => 'Post not found'], 401);
            }

            $post = Post::find($id);
            $post->description = $request->description;
            $post->save();

            return response()->json([ 'type' => 'success', 'message' => 'Post update successfully', 'data' => $post], 200);
        } catch (ModelNotFoundException $exception) {
            return response()->json([ 'type' => 'error', 'message' => 'Something went wrong, Please try again!'],401);
        }
    }

    /*** Post fetch by user API ***/
    public function userposts(Request $request)
    {
        $user_id = Auth::id();
        $blockedUserIds = Auth::user()->blockedUsers()->pluck('blocked_user');
        $posts = Post::with('postImages')->with('postCommunity')->with('comments')->with('user')->where('created_by', $request->id)->whereNotIn('created_by', $blockedUserIds)->orderBy('id', 'desc')->get();
        foreach ($posts as $key => $post) {
            $post_like = Post::withCount('likes')->find($post->id);
            $post->post_like_count = $post_like->likes_count;
            $post_comment = Post::withCount('commentcount')->find($post->id);
            $post->post_comment_count = $post_comment->commentcount_count;
            $post_like = PostLike::where('post_id',$post->id)->where('liked_by',$user_id)->first();
            if(!empty($post_like)){
                $post->likedbyme = 'true';
            }else{
                $post->likedbyme = 'false';
            }
        }
        return response()->json([ 'type' => 'success', 'message' => 'Post getting successfully', 'data' => $posts]);
    }

}