<?php

namespace App\Http\Controllers;

use App\Http\Models\MLBSchedule;
use App\Http\Models\MLBTeams;
use Illuminate\Http\Request;
use App\Http\Requests;

class ScheduleController extends Controller
{
    public function upcomingMatches(){
        $matches = MLBSchedule::orderBy('Day','desc')->take(20)->get();
        return $matches;
    }
}
