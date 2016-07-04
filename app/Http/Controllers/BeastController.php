<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BeastController extends Controller
{
    public function addToLog($data,$type){

        switch($type){
            case('error'):
                Log::error('Some error occurred at,'.Carbon::now()->toDateTimeString() . ' possible causes '. $data);
                break;
            case('info'):
                Log::info('Logging at ' . Carbon::now()->toDateTimeString()  .  $data);
                break;
        }
    }

    public function sendEmail($template,$data,$to_email,$to_name){

        \Mail::send('emails.'.$template, $data, function ($message) use ($to_email,$to_name) {
            $message->from(config('mail.from.address'), config('mail.from.name'));
            $message->to($to_email, $to_name)->subject('Password update!');
        });
    }
}
