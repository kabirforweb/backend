<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BeastController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Models\User;
use Hash;
use Illuminate\Support\Facades\Log;

class UserController extends BeastController
{
    private $user;

    public function __construct(){
        $this->user =  new User;
    }

    /**
     * Register User
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function register(Request $request){

        if( null === $request->email    ||
            null === $request->firstname
        ){
            return response()->json(['error' => 'One of the required field is empty'],401);
        }

        if($request->has('social_id') && $request->has('password')){
            return response()->json(['error' => 'One of the required field is empty'],401);
        }

        if($request->has('password') && strlen($request->get('password')) < 6){
            return response()->json(['error'=>'Password must be 6 characters in length'],500);
        }

        $user = $this->user->findByEmail($request->email);

        if(null != $user && !$request->has('social_id')){
            return response()->json(['error'=>'This email is already registered, please choose different one'],500);
        }elseif(null != $user && $request->has('social_id')){
            $token  =   JWTAuth::fromUser($user);
            return response()->json(compact('token','user'));
        }

        try{

            $this->user->firstname  =   $request->firstname;

            if($request->has('lastname')){
                $this->user->lastname  =   $request->get('lastname');
            }

            if($request->has('social_id')){
                $this->user->social_id  =   $request->get('social_id');
            }

            $this->user->email      =   $request->email;
            $this->user->password   =   Hash::make($request->password);
            $this->user->save();

        }catch (\Exception $e){

            $this->addToLog($e->getMessage(),'error');

            return response()->json(['error'=>'Failed to create user'],500);
        }

        $token  =   JWTAuth::fromUser($this->user);
        $user   =   $this->user;
        return response()->json(compact('token','user'));
    }

    /**
     * Authenticate user against email and password
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticate(Request $request, User $user)
    {
        if(null === $request->email || null === $request->password){
            return response()->json(['error' => 'One of the required field is empty'],401);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid Email/Password'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        $user   =   $user->findByEmail($request->email);
        return response()->json(compact('token','user'));
    }

    /**
     * Forgot Password
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function forgotPassword(Request $request){

        if( null === $request->email ){
            return response()->json(['error' => 'One of the required field is empty'],401);
        }

        $user   =   $this->user->findByEmail($request->email);

        if(!$user){
            return response()->json(['error' => 'Invalid user found'],401);
        }

        $to_email       =   $user->email;
        $to_name        =   $user->firstname . $user->lastname;
        $data['token']  =   str_random(40) . Carbon::now()->timestamp;

        $user->password_reset_token  =   $data['token'];

         try{

            $user->save();

            $this->sendEmail('forgot_password',$data,$to_email,$to_name);

        }catch (\Exception $e){

            Log::error('Failed to send forgot password email at') . $e->getMessage() . ' at Line No. ' . $e->getLine();
            return response()->json(['error' =>  'Some error occurred, please try again'],500);
        }

        return response()->json(['success'  =>  'An email has been sent to you, to reset password!']);
    }

    public function verifyForgotPasswordToken($token){

        if(null === $token){
            return response()->json(['error'=>'One of the required field is empty'],401);
        }

        $user   =   User::where('password_reset_token',$token)->first();

        if(!$user){
            return response()->json(['error'=>'This URL is no longer valid'],401);
        }

//        $user->password_reset_token =   '';
//
//        $user->save();

        return response()->json(['user_id'  =>  $user->id]);

    }

    public function confirmPassword(Request $request){

        if( null === $request->get('user_id')   ||
            null === $request->get('password')  ||
            null === $request->get('confirm_password')
        ){
            return response()->json(['error'=>'One of the required field is empty']);
        }

        if(strlen($request->get('password')) < 6){
            return response()->json(['error'=>'Password must be 6 characters in length'],500);
        }

        if($request->get('password') !== $request->get('confirm_password')){
            return response()->json(['error'=>'Password and confirm password does not match'],500);
        }

        $user   =   $this->user->find($request->get('user_id'));

        if(!$user){
            return response()->json(['error'=>'User doesn\'t exist in system'],500);
        }

        $user->password =   \Illuminate\Support\Facades\Hash::make($request->get('password'));
        $user->save();

        return response()->json(['success'  =>  'Password reset successfully']);

    }
}