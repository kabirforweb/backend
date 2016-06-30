<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Illuminate\Support\Facades\Log;

class ExternalAPIController extends Controller
{

    public function index(){

        // APIs URLs TO IMPORT SPORTS DATA - FANTASY
        $urls = [
            'mlb_team'          => 'https://api.fantasydata.net/mlb/v2/json/teams',
            'mlb_news'          => 'https://api.fantasydata.net/mlb/v2/json/News',
            'mlb_player'        => 'https://api.fantasydata.net/mlb/v2/json/Players',
            'mlb_stadium'       => 'https://api.fantasydata.net/mlb/v2/json/Stadiums',
            'mlb_schedual'      => 'https://api.fantasydata.net/mlb/v2/json/Games/2016',
            'mlb_playerseason'  => 'https://api.fantasydata.net/mlb/v2/json/PlayerSeasonStats/2016',
        ];

        foreach ($urls as $table => $url) {

        $data = json_decode($this->curl($url), true);

        $values_arr = [];

            foreach($data as $k=>$value) {

                    // REMOVE EXTRA KEY
                if(array_key_exists('Key', $value))

                unset($value['Key']);

                $value  = $this->filterArrayForTable($value,$table);

                if(array_key_exists('RunnerOnFirst',$value)) {
                   unset($value['RunnerOnFirst']);
                }

                if(array_key_exists('RunnerOnSecond',$value)){
                    unset($value['RunnerOnSecond']);
                }

                if(array_key_exists('RunnerOnThird',$value)){
                    unset($value['RunnerOnThird']);
                }

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
    }

    public function curl($send_url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Ocp-Apim-Subscription-Key: 43ed5d29a9b5413e8254a38b55ddf73c' ));
        curl_setopt($ch, CURLOPT_URL, $send_url);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new Exception("Failed retrieving  '" . $this->send_url . "' because of ' " . $error . "'.");
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