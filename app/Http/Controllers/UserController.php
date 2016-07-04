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
            null === $request->password ||
            null === $request->name
        ){
            return response()->json(['error' => 'One of the required field is empty'],401);
        }

        if(strlen($request->get('password')) < 6){
            return response()->json(['error'=>'Password must be 6 characters in length']);
        }

        $user = $this->user->findByEmail($request->email);

        if(null != $user ){
            return response()->json(['error'=>'Email already exists'],500);
        }

        try{

            $this->user->name  =   $request->name;
            $this->user->email  =   $request->email;
            $this->user->password  =   Hash::make($request->password);
            $this->user->save();

        }catch (\Exception $e){

            $this->addToLog($e->getMessage(),'error');

            return response()->json(['error'=>'Failed to create user'],500);
        }

        $token  =   JWTAuth::fromUser($this->user);

        return response()->json(compact('token'));
    }

    /**
     * Authenticate user against email and password
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticate(Request $request)
    {
        if(null === $request->email || null === $request->password){
            return response()->json(['error' => 'One of the required field is empty'],401);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json(compact('token'));
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
        $to_name        =   $user->name;
        $data['token']  =   str_random(40) . Carbon::now()->timestamp;

        $user->password_reset_token  =   $data['token'];

        try{

            $user->save();

            $this->sendEmail('forgot_password',$data,$to_email,$to_name);

        }catch (\Exception $e){
            return respose()->json(['error' =>  'Some error occurred, please try again']);
        }

        return response()->json(['success'  =>  'An email has been sent to you, to reset password!']);
    }

    public function confirmPassword(Request $request){

        if( null === $request->get('email')     ||
            null === $request->get('token')     ||
            null === $request->get('password')  ||
            null === $request->get('confirm_password')
        ){
            return response()->json(['error'=>'One of the required field is empty']);
        }

        $user   =   $this->user->findByEmail($request->get('email'));

        if(!$user){
            return response()->json(['error'=>'User doesn\'t exist in system']);
        }

        if($user->password_reset_token !== $request->get('token')){
            return response()->json(['error'=>'This link has expired or Invalid']);
        }

        if($request->get('password') !== $request->get('confirm_password')){
            return response()->json(['error'=>'Password and confirm password does not match']);
        }

        if(strlen($request->get('password')) < 6){
            return response()->json(['error'=>'Password must be 6 characters in length']);
        }

        $user->password_reset_token =   '';

        $user->save();

        return response()->json(['success'  =>  'Password reset successfully']);

    }
}