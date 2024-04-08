<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserCommunity;
use App\Models\Community;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UserContoller extends Controller
{
    public function updateUserCommunity(Request $request){
        $now = Carbon::now();
        try{
            $user_id = Auth::id();
            $community_ids = $request->community_ids;
            if(!empty($user_id) && count($community_ids)>0){
                foreach ($community_ids as $communityId) {
                    $community = new UserCommunity();
                    $community->community_id = $communityId;
                    $community->user_id = $user_id;
                    $community->joind_at = $now;
                    $community->save();
                }
                return response()->json(['type' => 'success', 'message' => 'User updated successfully'], 200);
            }else{
                return response()->json(['type' => 'error', 'message' => 'Something went wrong, Please try again.'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }

    /*** User Logout ***/
    public function logout(){
        $user = Auth::user();
        $user->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    /*** Update User Profile ***/
    public function updateUserProfile(Request $request)
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

        try{
            $id = Auth::id();
            $user = User::find($id);
            
            $user->name = $request->name ?? $user->name;
            $user->description = $request->description;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $name = time().'.'.$image->getClientOriginalExtension();
                $destinationPath = public_path('/profile_images');
                $image->move($destinationPath, $name);
                $user->profile_image = '/public/profile_images/'.$name;
            }
            $user->save();
            return response()->json(['type' => 'success', 'message' => 'User profile update successfully', 'data' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }

    /*** API: Getting User Media Like Images, Video */
    public function getUserMedia(Request $request){
        try{
            $id = Auth::id();
            $user = User::find($id);
            $media = $user->userMedai;

            return response()->json(['type' => 'success', 'message' => 'User media getting successfully', 'data' => $media], 200);
        } catch (\Exception $e) {
            return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }

    /*** API: Getting User Communities */
    public function getUserCommunity(Request $request){
        try{
            $id = Auth::id();
            $user = User::find($id);
            $user_communities = $user->userCommunities;
            $community_array = array();
            if(count($user_communities)>0){
                foreach ($user_communities as $key => $user) {
                    $community = Community::where('id',$user->community_id)->first();
                    $data['community'] = @$community;
                    $data['community']['user'] = User::find($id);
                    $community_array[] = $data;
                }
            }
            return response()->json(['type' => 'success', 'message' => 'User communities getting successfully', 'data' => $community_array], 200);
        } catch (\Exception $e) {
            return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }

    /*** API: Getting User Detail */
    public function getUserDetail(Request $request){
        try{
            $id = Auth::id();
            
            $user = User::find($id);
            return response()->json(['type' => 'success', 'message' => 'User data getting successfully', 'data' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }

        /*** API: Getting User throw Community id  */
    public function getCommunityUsers(Request $request){
        try{
            $community_id = $request->community_id;
            if($community_id){
            $userIdsInCommunity = UserCommunity::where('community_id', $community_id)->pluck('user_id');

            $usersInSameCommunity = User::whereIn('id', $userIdsInCommunity)->get()->toArray();
           
            return response()->json(['type' => 'success', 'message' => 'Users getting successfully', 'data' => $usersInSameCommunity], 200);
            }else{
            return response()->json(['type' => 'error', 'message' => 'Please select valid community'], 200);
                
            }
        } catch (\Exception $e) {
            return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }
        // update device token
        public function quote(Request $request)
    {
        try {
            $data = DB::table('quotes')->get()->toArray();

            return response()->json([
                'status' => true,
                'message' => 'Notification sent successfully',
                'data' => $data
            ]);
           
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
    // update device token
    public function updateLatLong(Request $request)
    {
        try {
            $id = Auth::id();
            $user = User::find($id);

            $user->latitude = $request->latitude;
            $user->longitude = $request->longitude;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Latitude and longitude updated successfully',
                'data' => $user
            ]);
           
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
        // update device token
    // public function usersdistance(Request $request)
    // {
    //     try {
    //         dd('enter');
            // $id = Auth::id();
            // $user = User::find($id);

            // $latitude = $user->latitude;
            // $longitude = $user->longitude;
            // $radius = 10;
            // $nearbyUsers = User::select('id', 'name', 'latitude', 'longitude')
            //     ->whereRaw("calculateDistance(latitude, longitude, $latitude, $longitude) <= $radius")
            //     ->get();
            // dd($nearbyUsers);

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Latitude and longitude updated successfully',
    //             'data' => $user
    //         ]);
           
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }

        /*** API: Getting User Detail */
    public function getUsersDetail(Request $request){
        try{
            $id = $request->id;
            
            $user = User::find($id);
           
            return response()->json(['type' => 'success', 'message' => 'User data getting successfully', 'data' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }

        public function mapUsers(Request $request) {
    try {
        $id = Auth::id();

        $user = User::find($id);
        $userLatitude = $user->latitude;
        $userLongitude = $user->longitude;

        $radius = 10;

        $usersWithinRadius = [];

        $allUsers = User::all()->toArray();

        foreach ($allUsers as $otherUser) {
            $otherUserLatitude = $otherUser['latitude'];
            $otherUserLongitude = $otherUser['longitude'];
            $distance = $this->haversineDistance(
                $userLatitude, $userLongitude,
                $otherUserLatitude, $otherUserLongitude
            );

            if ($distance <= $radius) {
                $usersWithinRadius[] = $otherUser;
            }
        }

        return response()->json([
            'type' => 'success',
            'message' => 'Users within the specified radius',
            'data' => $usersWithinRadius
        ], 200);
    } catch (\Exception $e) {
        return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }

    // Function to calculate Haversine distance
    private function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Radius of the Earth in kilometers
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }


}
