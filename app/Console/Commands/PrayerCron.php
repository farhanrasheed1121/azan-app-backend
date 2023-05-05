<?php

namespace App\Console\Commands;

use App\Models\GuestUser;
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
        $user = GuestUser::get();
        foreach ($user as $usr) {
            // \Log::info($usr['time_zone']);
            // exit;

            $longitude = $usr['longitude'];
            $latitude = $usr['latitude'];
            $timezone = $usr['time_zone'];
            $receiverId = $usr['device_id'];
            $url = 'https://api.aladhan.com/v1/timings?latitude=' . $latitude . '&longitude=' . $longitude . '&method=2';
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            // Get the prayer times from the API response
            $fajrTime = $data['data']['timings']['Fajr'];
            $dhuhrTime = $data['data']['timings']['Dhuhr'];
            $asrTime = $data['data']['timings']['Asr'];
            $maghribTime = $data['data']['timings']['Maghrib'];
            $ishaTime = $data['data']['timings']['Isha'];
            $responseData = [

                'fajr_time' => $fajrTime,
                'dhuhr_time' => $dhuhrTime,
                'asr_time' => $asrTime,
                'maghrib_time' => $maghribTime,
                'isha_time' => $ishaTime,
            ];

            $timestamp = Carbon::now()->timestamp;

            $userTime = Carbon::createFromTimestamp($timestamp, $timezone);

            // Format as desired
            $userTimeFormatted = $userTime->format('H:i');

            $current = $userTimeFormatted;
            $prayers = [
                'fajr' => $responseData['fajr_time'],
                'dhuhr' => $responseData['dhuhr_time'],
                'asr' => $responseData['asr_time'],
                'maghrib' => $responseData['maghrib_time'],
                'isha' => $responseData['isha_time'],
            ];
                // \Log::info("The current prayer is $prayers.");

            $currentPrayer = array_search($current, $prayers);

            if ($currentPrayer !== false) {
                // \Log::info("The current prayer is $currentPrayer.");

                $message = "Your $currentPrayer time is start.";
                $title = 'Prayer time';
                $content = array(
                    "en" => $message
                );
                $heading = array(
                    "en" => $title
                );
                $fields = array(
                    'app_id' => "45f5ce3b-d7f2-49f5-9f6e-80835e0385b5",
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
                    'Authorization: Basic NDExYTc3NWUtYWRlYi00YTFjLWJjMDItNzViOTg1NGVlYzMw'
                ));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

                $response = curl_exec($ch);
                curl_close($ch);
            }

            // $dbStartDateTimeFormate = date('Y-m-d H:i', strtotime($dbStartDateTimeFormate . "+ $half_val minutes"));
            // }
                // \Log::info("The current prayer is $currentPrayer.");

        }
        // \Log::info("Cron is working fine!");
    }
}
