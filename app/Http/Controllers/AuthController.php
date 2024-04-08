<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Support\Facades\Auth;
use App\Mail\SendOtp;
use App\Mail\SendRegisterEmail;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /*** Registration ***/
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:4',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }           
        }

        $otp = $this->generateOtp();
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'otp' => $otp
        ]);
        $token = $user->createToken('LaravelAuthApp')->accessToken;
        /*** Send Email ***/
        $email_data = [
            "otp" => $otp,
            "name" => $request->name
        ];
        $email = new SendRegisterEmail($email_data);
        Mail::to($request->email)->send($email);

        return response()->json(['type' => 'success', 'token' => $token, 'otp' => $otp], 200);
    }
 
    /*** Login  ***/
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }           
        }
        // Check user is exit in our record 
        $user_exit  = User::where('email',$request->email)->first();
        if(empty($user_exit)){
            return response()->json(['type' => 'error', 'message' => 'User not found' ], 401);
        }
        //check user is verified or not
        $is_verify = User::where('email',$request->email)->where('is_verify','1')->first();
        if(empty($is_verify)){
            $user_obj = User::where('email',$request->email)->first();
            $user = User::find($user_obj->id);
            $token = $user->createToken('LaravelAuthApp')->accessToken;
            return response()->json(['type' => 'error', 'message' => 'your account is not verified', 'token' => $token, 'is_verify' => $user_obj->is_verify], 401);
        }

        $data = [
            'email' => $request->email,
            'password' => $request->password
        ];
 
        if (auth()->attempt($data)) {
            $user_obj = User::where('email',$request->email)->first();
            $token = auth()->user()->createToken('LaravelAuthApp')->accessToken;
            return response()->json(['type' => 'success', 'message' => 'You have successfully login', 'token' => $token, 'is_verify' => $user_obj->is_verify], 200);
        }else{
            return response()->json(['type' => 'error', 'message'=>'Username or password is wrong'], 401);   
        }
    }
    
    /*** Create OTP ***/
    public function generateOtp(){
        $otp = rand(1234, 9999);
        return $otp;
    }

    /*** Verify User */
    public function verifyUser(Request $request){
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'otp' => 'required',
        ]);

        if ($validator->fails()) { 
            $errors = $validator->messages();
            foreach ($errors->all() as $error) {
                return response()->json(['type' => 'error', 'message'=>$error], 401);    
            }             
        }

        $otp = $request->otp;
        $accessToken = $request->token; 
        $token_parts = explode('.', $accessToken);
        $token_header = $token_parts[1];
        $token_header_json = base64_decode($token_header);
        $token_header_array = json_decode($token_header_json, true);
        $token_id = $token_header_array['jti'];
        $userId = DB::table('oauth_access_tokens')->where('id', $token_id)->value('user_id');
        if(!empty($userId) && !empty($otp)){
            $user = DB::table('users')->where('id', $userId)->where('otp',$otp)->first();
            if(empty($user)){
                return response()->json(['type' => 'error', 'message' => 'No user found!'], 401);
            }
            $update_user = DB::table('users')->where('id', $user->id)->update(array('is_verify' => '1', 'otp' => ''));
            if($update_user){
                $user = User::find($user->id);
                $token = $user->createToken('LaravelAuthApp')->accessToken;
                return response()->json(['type' => 'success', 'message' => 'Your account successfully verified', 'token' => $token], 200);
            }
        }else{
            return response()->json(['type' => 'error', 'message' => 'Something went wrong, Please try again.'], 401);
        }
    }
    
    /*** Check Social User Email Exit or not ***/
    public function socialUserExit(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'type' => 'required'
        ]);

        $errors = $validator->messages();
        foreach ($errors->all() as $error) {
            return response()->json(['type' => 'error', 'message'=>$error], 401);    
        } 
        $provider_id = $request->type.'_id';
        $user = User::where('provider',$request->type)->where($provider_id,$request->id)->first();
        if(empty($user)){
            return response()->json(['type' => 'error', 'message' => 'No user found'], 401);
        }
        $user = User::find($user->id);
        $token = $user->createToken('LaravelAuthApp')->accessToken;
        return response()->json(['type' => 'success', 'message' => 'auth token successfully generated', 'token' => $token], 200);
    }

    /*** Social Login ***/
    public function socialLogin(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'email' => 'required|email'
        ]);

        $errors = $validator->messages();
        foreach ($errors->all() as $error) {
            return response()->json(['type' => 'error', 'message'=>$error], 401);    
        } 
        $provider_id = $request->type.'_id';
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            $provider_id => $request->id,
            'provider' => $request->type,
            'is_verify' => '1',
        ]);

        Auth::login($user);
        $token = $user->createToken('LaravelAuthApp')->accessToken;
        return response()->json(['type' => 'success', 'token' => $token ], 200);
    }

    /*** Request for forgot password */
    public function forgotPasswordRequest(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        $errors = $validator->messages();
        foreach ($errors->all() as $error) {
            return response()->json(['type' => 'error', 'message'=>$error], 401);    
        } 

        $user = User::where('email',$request->email)->first();
        if(empty($user)){
            return response()->json(['type' => 'error', 'message'=>'User not found'], 401);  
        }
        $otp = $this->generateOtp();
        $record = User::find($user->id);
        $record->otp = $otp;
        $record->save();
        $token = $record->createToken('LaravelAuthApp')->accessToken;
        $this->sendEmail($record->email, $record->name, $otp);
        return response()->json(['type' => 'success', 'otp' => $otp, 'message' => 'Otp successfully send to your email', 'token' => $token], 200);
    }

    /*** verify Forgot Password Code ***/
    public function verifyForgotPasswordCode(Request $request){
        $validator = Validator::make($request->all(), [
            'otp' => 'required',
            'token' => 'required'
        ]);

        $errors = $validator->messages();
        foreach ($errors->all() as $error) {
            return response()->json(['type' => 'error', 'message'=>$error], 401);    
        } 
        
        $otp = $request->otp;
        $accessToken = $request->token; 
        $token_parts = explode('.', $accessToken);
        $token_header = $token_parts[1];
        $token_header_json = base64_decode($token_header);
        $token_header_array = json_decode($token_header_json, true);
        $token_id = $token_header_array['jti'];
        $userId = DB::table('oauth_access_tokens')->where('id', $token_id)->value('user_id');
        if(!empty($userId) && !empty($otp)){
            $user = DB::table('users')->where('id', $userId)->where('otp',$otp)->first();
            if(empty($user)){
                return response()->json(['type' => 'error', 'message' => 'No user found!'], 401);
            }
            return response()->json(['type' => 'success', 'message' => 'Your Otp matched' ], 200);
            // $update_user = DB::table('users')->where('id', $user->id)->update(array('password' => bcrypt($request->password), 'otp' => ''));
            // if($update_user){
            //     $user = User::find($user->id);
            //     $token = $user->createToken('LaravelAuthApp')->accessToken;
            //     return response()->json(['type' => 'success', 'message' => 'Your password successfully changed', 'token' => $token], 200);
            // }
        }else{
            return response()->json(['type' => 'error', 'message' => 'Something went wrong, Please try again.'], 401);
        }
    }

    /*** Update/Chnage User password ***/
    public function updateUserPassword(Request $request){
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:8'
        ]);

        $errors = $validator->messages();
        foreach ($errors->all() as $error) {
            return response()->json(['type' => 'error', 'message'=>$error], 401);    
        } 
        $accessToken = $request->token; 
        $token_parts = explode('.', $accessToken);
        $token_header = $token_parts[1];
        $token_header_json = base64_decode($token_header);
        $token_header_array = json_decode($token_header_json, true);
        $token_id = $token_header_array['jti'];
        $userId = DB::table('oauth_access_tokens')->where('id', $token_id)->value('user_id');
        if(!empty($userId)){
            $user = DB::table('users')->where('id', $userId)->first();
            if(empty($user)){
                return response()->json(['type' => 'error', 'message' => 'No user found!'], 401);
            }
            $update_user = DB::table('users')->where('id', $user->id)->update(array('password' => bcrypt($request->password), 'otp' => ''));
            if($update_user){
                $user = User::find($user->id);
                $token = $user->createToken('LaravelAuthApp')->accessToken;
                return response()->json(['type' => 'success', 'message' => 'Your password successfully changed', 'token' => $token], 200);
            }
        }else{
            return response()->json(['type' => 'error', 'message' => 'Something went wrong, Please try again.'], 401);
        }
    }

    /*** Resend OTP Using Register token */
    public function resendOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);

        $errors = $validator->messages();
        foreach ($errors->all() as $error) {
            return response()->json(['type' => 'error', 'message'=>$error], 401);    
        } 
        $otp = $this->generateOtp();
        $accessToken = $request->token; 
        $token_parts = explode('.', $accessToken);
        $token_header = $token_parts[1];
        $token_header_json = base64_decode($token_header);
        $token_header_array = json_decode($token_header_json, true);
        $token_id = $token_header_array['jti'];
        $userId = DB::table('oauth_access_tokens')->where('id', $token_id)->value('user_id');
        if(!empty($userId)){
            $user = DB::table('users')->where('id', $userId)->first();
            if(empty($user)){
                return response()->json(['type' => 'error', 'message' => 'No user found!'], 401);
            }
            $update_user = DB::table('users')->where('id', $user->id)->update(array('otp' => $otp));
            if($update_user){
                $user = User::find($user->id);
                $token = $user->createToken('LaravelAuthApp')->accessToken;

                /*** Send Email ***/
                $email_data = [
                    "otp" => $otp,
                    "name" => $user->name
                ];
                $email = new SendRegisterEmail($email_data);
                Mail::to($user->email)->send($email);

                return response()->json(['type' => 'success', 'message' => 'Otp successfully send to your email', 'otp' => $otp], 200);
            }
        }
    }

    /*** Send Email ***/
    public function sendEmail($email_address, $name, $otp)
    {
        $email_data = [
            "otp" => $otp,
            "name" => $name
        ];
        $email = new SendOtp($email_data);
        Mail::to($email_address)->send($email);
        return "Email sent successfully!";
    }
}