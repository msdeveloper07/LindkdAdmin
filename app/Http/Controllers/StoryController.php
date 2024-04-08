<?php

namespace App\Http\Controllers;
use App\Models\Story;
use App\Models\StoryRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
use Carbon\Carbon;

class StoryController extends Controller
{
    /*** API Getting Stories ***/
    public function getStories()
    {   
        $user_id = Auth::id();
        $currentDateTime = Carbon::now();
        $stories = Story::where('expires_at', '>', $currentDateTime)->with('user')->orderBy('id', 'desc')->get();
        foreach ($stories as $key => $story) {
            $readBy = StoryRead::where('read_by',$user_id)->where('story_id',$story->id)->get()->count();
            if($readBy>0){
                $story->readbyuser = 'true';
            }else{
                $story->readbyuser = 'false';
            }
        }
        return response()->json([ 'type' => 'success', 'message' => 'Stories listing.', 'data' => $stories], 200);
    }

    /*** API Create Story ***/
    public function createStory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240', // Maximum file size of 10MB
        ]);
        
        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            } 
        }

        $user_id = Auth::id();
        // $story = new Story;
        // if ($request->hasFile('image')) {
        //     $image = $request->file('image');
        //     $name = time().'.'.$image->getClientOriginalExtension();
        //     $destinationPath = public_path('/userstories');
        //     $image->move($destinationPath, $name);
        //     $story->image = '/public/userstories/'.$name;
        // }
        // if(!empty($request->video_url)){
        //     $story->video = $request->video_url;
        // }
        // $story->user_id = $user_id;
        // $story->description = $request->description;
        // $story->expires_at = Carbon::now()->addDay();
        // $story->save();

        // Using S3 Links for Images and Video
        $media_decode = json_decode($request->media, true);
        foreach ($media_decode as $index => $item) {
            $thumbnail = $item['thumbnail'];
            $video = $item['video'];
            $type = $item['type'];
            // Save Story
            $story = new Story;
            $story->image = $thumbnail;
            $story->video = $video;
            $story->user_id = $user_id;
            $story->description = $request->description;
            $story->expires_at = Carbon::now()->addDay();
            $story->save();
        }

        return response()->json(['type' => 'success', 'message' => 'Story added successfully!', 'data' => $story], 200);
    }

    /*** API User able to delete own story ***/
    public function deleteUserStory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'story_id' => 'required',
        ]);
        
        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }           
        }
        $id = $request->story_id;
        $user_id = Auth::id();
        $story = Story::where('user_id',$user_id)->where('id',$id)->get();
        if(!$story){
            return response()->json([ 'type' => 'error', 'message' => 'Story not found'], 401);
        }

        $story = Story::find($id);
        $story->delete();

        return response()->json([ 'type' => 'success', 'message' => 'Story deleted successfully.'], 200);
    }

    /*** Set Story Read By Status By User ***/
    public function readStoryByUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'story_id' => 'required',
        ]);
        
        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }           
        }
        $id = $request->story_id;
        $user_id = Auth::id();
        $story = Story::where('user_id',$user_id)->where('id',$id)->get();
        if(!$story){
            return response()->json([ 'type' => 'error', 'message' => 'Story not found'], 401);
        }

        $checkIF = StoryRead::where('read_by',$user_id)->where('story_id',$id)->get()->count();
        if($checkIF>0){
            return response()->json(['type' => 'error', 'message'=>'User alreay view this story.'], 401); 
        }

        // StoryRead
        $storyread = new StoryRead;
        $storyread->read_by = $user_id;
        $storyread->story_id = $id;
        $storyread->save();

        return response()->json([ 'type' => 'success', 'message' => 'Story view successfully.'], 200);
    }

}