<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\Community;

class CommunityContoller extends Controller
{
    /*** Create Community */
    public function createCommunity(Request $request){
        try {
            $request->validate([
                'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif',
            ]);
            if ($request->file('thumbnail')->isValid()) {
                $image = $request->file('thumbnail');
                $name = time().'.'.$image->getClientOriginalExtension();
                $destinationPath = public_path('/community_images');
                $image->move($destinationPath, $name);
                $community = Community::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'thumbnail' => '/public/community_images/'.$name,
                    'cover_image' => '/public/community_images/'.$name
                ]);
                return response()->json(['type' => 'success', 'message' => 'Community successfully created', 'data' => $community ], 200);
            }
            return response()->json(['type' => 'error', 'message' => 'Invalid image'], 401);
        } catch (\Exception $e) {
            return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }

    /*** Community Listing API */
    public function getCommunities(Request $request){
        try {
            $community = Community::get();
            return response()->json(['type' => 'success', 'message' => 'Community data', 'data' => $community ], 200);
        } catch (\Exception $e) {
            return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }
}
