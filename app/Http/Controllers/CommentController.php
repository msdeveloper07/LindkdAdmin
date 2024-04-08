<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Validator;

class CommentController extends Controller
{
    /*** Add Comment on Post ***/
    public function addCommentToPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required',
            'post_id' => 'required',
        ]);
        
        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }           
        }

        $user_id = Auth::id();
        $id = $request->post_id;
        $post = Post::findOrFail($id);
        if (!$post) {
            return response()->json([ 'type' => 'error', 'message' => 'Post not found'], 401);
        }

        $comment = new Comment();
        $comment->post_id = $id;
        $comment->content = $request->comment;
        $comment->parent_id = @$request->parent_id;
        $comment->user_id = $user_id;
        $comment->save();

        return response()->json([ 'type' => 'success', 'message' => 'Comment added successfully.', 'data' => $comment], 200);
    }

    /*** Get Listing of Comment*/
    public function getComments(Request $request){
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);
        
        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }           
        }

        $user_id = Auth::id();
        $id = $request->post_id;
        $post = Post::findOrFail($id);
        if (!$post) {
            return response()->json([ 'type' => 'error', 'message' => 'Post not found'], 401);
        }

        $comments = Comment::where('post_id',$id)->whereNull('parent_id')->where('user_id',$user_id)->with('user')->with('replies')->orderBy('id', 'desc')->get();

        return response()->json([ 'type' => 'success', 'message' => 'Comment listing.', 'data' => $comments], 200);
    }
}