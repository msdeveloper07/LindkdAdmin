<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserCommunity;
use App\Models\Community;
use App\Models\UserReport;
use App\Models\UserBlock;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Mail\ReportUser;
use Illuminate\Support\Facades\Mail;

class EndUserContoller extends Controller
{
    /*** API: Getting EndUser Media Like Images, Video */
    public function getEndUserMedia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);
        
        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            } 
        }

        try{
            $id = $request->user_id;;
            $user = User::find($id);
            $media = $user->userMedai;

            return response()->json(['type' => 'success', 'message' => 'User media getting successfully', 'data' => $media], 200);
        } catch (\Exception $e) {
            return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }

    /*** API: Getting EndUser Communities */
    public function getEndUserCommunity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);
        
        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            } 
        }

        try{
            $id = $request->user_id;;
            $user = User::find($id);
            $user_communities = $user->userCommunities;
            $community_array = array();
            if(count($user_communities)>0){
                foreach ($user_communities as $key => $user) {
                    $community = Community::where('id',$user->community_id)->first();
                    $community_array[] = @$community;
                }
            }
            return response()->json(['type' => 'success', 'message' => 'User communities getting successfully', 'data' => $community_array], 200);
        } catch (\Exception $e) {
            return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }

    /*** API: Report ***/
    public function userReportToAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reported_user' => 'required',
        ]);
        
        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            } 
        }

        try{
            $reported_by = Auth::id();
            $reported_user = $request->reported_user;

            $report = new UserReport();
            $report->reported_by = $reported_by;
            $report->reported_user = $reported_user;
            $report->description = $request->description;
            $report->save();

            $reported_by = User::find($reported_by);
            $reported_user = User::find($reported_user);

            /*** Send Email ***/
            $email_data = [
                "reported_by" => $reported_by->name,
                "reported_user" => $reported_user->name,
                "description" => $request->description
            ];
            $email = new ReportUser($email_data);
            Mail::to('admin@linkd.com')->send($email);

            return response()->json(['type' => 'success', 'message' => 'Report successfully submitted to Liked Team!'], 200);

        } catch (\Exception $e) {
            return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }

    /*** API: Block User ***/
    public function blockUser(Request $request)
    {
        try{
            $blocked_by = Auth::id();
            $blocked_user = $request->blocked_user;
            $blocked_community = $request->blocked_community;

            if($blocked_user){
            $checkUserBlock = UserBlock::where('blocked_by', $blocked_by)
                ->where('blocked_user', $blocked_user)
                ->get()->toArray();
                if($checkUserBlock){
                    return response()->json(['type' => 'error', 'message' => 'User is already blocked'], 400);
                }
            }
            if($blocked_community){
                $checkUserBlock = UserBlock::where('blocked_by', $blocked_by)
                ->where('blocked_community', $blocked_community)
                ->get()->toArray();
                // UserCommunity
                $UserCommunity = DB::table('user_communities')->where('user_id', $blocked_by)->where('community_id', $blocked_community)->delete();
                
                if($checkUserBlock){
                    return response()->json(['type' => 'error', 'message' => 'Community is already blocked'], 400);
                }
            }
            if($blocked_user){
                $block = new UserBlock();
                $block->blocked_by = $blocked_by;
                $block->blocked_user = $blocked_user;
                $block->save();

            return response()->json(['type' => 'success', 'message' => 'User block successfully'], 200);
            }else{
                $block = new UserBlock();
                $block->blocked_by = $blocked_by;
                $block->blocked_community = $blocked_community;
                $block->save();

            return response()->json(['type' => 'success', 'message' => 'Community block successfully'], 200);
            }

        } catch (\Exception $e) {
            return response()->json(['status'=>false,'message' =>$e->getMessage()],400);
            // return response()->json(['type' => 'error', 'message' => 'An error occurred'], 401);
        }
    }

    /*** Getting Users Details Behaf of UsersID ***/
    public function gettingEndUsers(Request $request){
        // $id = $request->id;
        $id = Auth::id();
        $checkUserBlock = UserBlock::where('blocked_by', '0')->get()->toArray();
       
        $blockedByArray = [];

        foreach ($checkUserBlock as $user) {
            $blockedByArray[] = $user['blocked_user'];
        }
        
        $user_ids = $request->user_ids;
        $user_ids = array_diff($user_ids, $blockedByArray); 
        if (count($user_ids)>0) {
            $users = User::whereIn('id',$user_ids)->get();

            return response()->json(['type' => 'success', 'message' => 'Users data getting successfully', 'data' => $users], 200);
        }else{
            // return response()->json(['status'=>false,'message' =>$e->getMessage()],400);
            return response()->json(['type' => 'success', 'message' => 'Chat Box empty.'], 401);
        }
    }

}
