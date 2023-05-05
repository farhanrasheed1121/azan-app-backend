<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrayerCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prayer:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // return 0;
        $user = User::get();
        foreach ($user as $usr) {
            if ($usr->is_push === 1) {
                $longitude = $usr['longitude'];
                $latitude = $usr['latitude'];
                $url = 'https://api.aladhan.com/v1/timings?latitude=' . $latitude . '&longitude=' . $longitude . '&method=2';
                $response = file_get_contents($url);
                $data = json_decode($response, true);

                // Get the prayer times from the API response
                $fajrTime = $data['data']['timings']['Fajr'];
                $dhuhrTime = $data['data']['timings']['Dhuhr'];
                $asrTime = $data['data']['timings']['Asr'];
                $maghribTime = $data['data']['timings']['Maghrib'];
                $ishaTime = $data['data']['timings']['Isha'];
                // Get the prayer times for the user's location

                // Return the prayer times as a JSON response
                $responseData = [

                    'fajr_time' => $fajrTime,
                    'dhuhr_time' => $dhuhrTime,
                    'asr_time' => $asrTime,
                    'maghrib_time' => $maghribTime,
                    'isha_time' => $ishaTime,
                ];
                // $currentTime = Carbon::now()->format('H:i');



                // Get current timestamp
                $timestamp = Carbon::now()->timestamp;

                // Get timezone at user's location
                $timezone = timezone_name_from_latitude_and_longitude($latitude, $longitude);

                // Convert to user's local time
                $userTime = Carbon::createFromTimestamp($timestamp, $timezone);

                // Format as desired
                $userTimeFormatted = $userTime->format('H:i');
                // \Log::info($userTimeFormatted);
        // \Log::info("Cron is working fine!");
                

                if ($userTimeFormatted == $responseData) {
                    $getQuote = DB::table('quotes')->inRandomOrder()->limit(1)->get();
                    $message = $getQuote[0]->quote;

                    $title = 'Daily Quote';
                    $content = array(
                        "en" => $message
                    );
                    $heading = array(
                        "en" => $title
                    );
                    $fields = array(
                        'app_id' => "875fa3ce-631c-45d1-aea5-bb8f559316f2",
                        'include_external_user_ids' => array("$receiverId"),
                        'contents' => $content,
                        'headings' => $heading,
                        // 'data' => $data
                    );

                    $fields = json_encode($fields);

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json; charset=utf-8',
                        'Authorization: Basic ZmQxMTE2ZjctMjEzOC00YWYyLWI5MzItMGNmMmJiNDhlZjk1'
                    ));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_HEADER, FALSE);
                    curl_setopt($ch, CURLOPT_POST, TRUE);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

                    $response = curl_exec($ch);
                    curl_close($ch);
                }

                $dbStartDateTimeFormate = date('Y-m-d H:i', strtotime($dbStartDateTimeFormate . "+ $half_val minutes"));
                // }
            }
        }
        // \Log::info("Cron is working fine!");
    }
}
