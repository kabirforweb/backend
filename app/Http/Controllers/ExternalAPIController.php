<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Http\Requests;
use DB;
use Illuminate\Support\Facades\Log;

class ExternalAPIController extends Controller
{

    const API_URL                                       =   'https://api.fantasydata.net/mlb/v2/json';
    const URL_SEPARATOR                                 =   '/';

    //-- Endpoints
    const MLB_TEAMS                                     =   'teams';
    const MLB_ARE_GAME_IN_PROGRESS                      =   'AreAnyGamesInProgress';
    const MLB_BATTERS_VS_PITCHERS_STATS                 =   'HitterVsPitcher';
    const MLB_BOX_SCORES                                =   'BoxScore';
    const MLB_BOX_SCORES_BY_DATE                        =   'BoxScores';
    const MLB_BOX_SCORES_BY_DATE_DELTA                  =   'BoxScoresDelta';
    const MLB_PLAYER_GAMES_STAT_BY_DATE                 =   'PlayerGameStatsByDate';
    const MLB_PLAYER_GAMES_STAT_BY_PLAYER               =   'PlayerGameStatsByPlayer';
    const MLB_PLAYER_SEASON_SPLIT_STATS                 =   'PlayerSeasonSplitStats';
    const MLB_PLAYER_SEASON_STATS                       =   'PlayerSeasonStats';
    const MLB_PROJECTED_PLAYER_GAME_STATS_BY_DATE       =   'PlayerGameProjectionStatsByDate';
    const MLB_PROJECTED_PLAYER_GAME_STATS_BY_PLAYER     =   'PlayerGameProjectionStatsByPlayer';
    const MLB_TEAM_GAME_STAT_BY_DATE                    =   'TeamGameStatsByDate';
    const MLB_TEAM_SEASON_STATS                         =   'TeamSeasonStats';
    const MLB_NEWS                                      =   'News';
    const MLB_PLAYER                                    =   'Players';
    const MLB_STADIUM                                   =   'Stadiums';
    const MLB_SCHEDULE                                  =   'Games/2016';


    public function teams(){
       return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_team');
    }

    public function AreAnyGamesInProgress(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_are_any_games_in_progress');
    }

    public function HitterVsPitcher(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_ARE_GAME_IN_PROGRESS, 'mlb_hitters_vs_pitcher');
    }

    public function BoxScore(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_box_score');
    }

    public function BoxScoresByDelta(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_box_scores_by_delta');
    }

    public function PlayerGameStatsByDate(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_player_game_stats_by_date');
    }

    public function PlayerGameStatsByPlayer(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_player_game_stats_by_player');
    }

    public function PlayerSeasonSplitStats(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_player_season_split_stats');
    }

    public function PlayerSeasonStats(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_player_season_stats');
    }

    public function PlayerGameProjectionStatsByDate(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_player_game_projection_stats_by_date');
    }

    public function PlayerGameProjectionStatsByPlayer(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_player_game_projection_stats_by_player');
    }

    public function TeamGameStatsByDate(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_team_game_stats_by_date');
    }

    public function News(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_news');
    }

    public function Stadiums(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_stadium');
    }

    public function Schedules(){
        return $this->processData(self::API_URL . self::URL_SEPARATOR . self::MLB_TEAMS, 'mlb_schedual');
    }

    public function processData($url,$table){

        $data   =   $this->curl($url);

        if(!json_decode($data)){
            Log::error('Failed to pull data for API URL : ' . $url);
            return false;
        }

        return $this->addToDB($table,$data);
    }

    public function addToDB($table,$data){

        $this->checkTableAndColumns();

        if(empty($data) || !is_array($data) || !count($data)){
            return false;
        }

        foreach($data as $k=>$value) {

            // REMOVE EXTRA KEY
            if(array_key_exists('Key', $value))

                unset($value['Key']);

            $value  = $this->filterArrayForTable($value,$table);

            $columns_str = implode(",", array_keys($value));

            $value = preg_replace("/[^a-zA-Z 0-9]+/", "", $value );
            $values_str = implode("','", $value);

            $values_arr[] = "('".$values_str."')";
        }

        $final_values = implode(",", $values_arr);

        try{
            // TRUNCATE EXISTING DATA
            DB::statement("TRUNCATE TABLE ".$table);

            // INSERT NEW VALUES
            $insert_query_string = DB::insert("INSERT INTO ".$table." (".$columns_str.") VALUES ".$final_values);

            if($insert_query_string) {
                Log::info("Records inserted successfully for table " . $table . " Date" . Carbon::now()->toDayDateTimeString());
            }
        }catch (Exception $e){
            Log::info("Failed to add record for table " . $table . " Date" . Carbon::now()->toDayDateTimeString());
        }
    }

//    public function checkTableAndColumns($table,$cols){
//
//
//    }

    public function isTableExists($table){

    }

    public function curl($send_url) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Ocp-Apim-Subscription-Key: 43ed5d29a9b5413e8254a38b55ddf73c' ));
        curl_setopt($ch, CURLOPT_URL, $send_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);

            Log::error("Failed retrieving  '" . $send_url . "' because of ' " . $error . "'.");
        }
        return $result;
    }

    public function filterArrayForTable($row,$table){

        foreach($row as $key=>$val){
            $q = DB::select("SELECT * FROM information_schema.COLUMNS WHERE
                    TABLE_SCHEMA = '".env('DB_DATABASE')."' AND
                    TABLE_NAME = '$table' AND COLUMN_NAME = '$key'");
            if(!$q){
                unset($row[$key]);
            }
        }
        return $row;
    }
}