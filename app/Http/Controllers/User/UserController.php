<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrayerTimeRequest;
use App\Http\Traits\ResponseTrait;
use App\Models\CommunityQoute;
use App\Models\IslamicQoute;
use App\Models\Post;
use App\Models\PrayerTime;
use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
// use GeniusTS\LaravelHijriDate\Hijri;
// use GeniusTS\HijriDate\Date;
use TimezoneMapper\TimezoneMapper;
use \Carbon\Carbon;




class UserController extends Controller
{
    use ResponseTrait;
    public function alQuran(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'surah_number' => 'required',

        ]);
        if ($validator->fails()) {

            $errors = $this->sendError(implode(",", $validator->errors()->all()));
            throw new HttpResponseException($errors, 422);
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->get('http://api.alquran.cloud/v1/surah/' . $request->surah_number . '/ar.alafasy ');
        $responseBody = $response->body();

        $test = json_decode($responseBody, true);
        if ($test['status'] == false) {
            return $this->sendError('Unable to proccess. Please try again later');
        }
        $text = $test['data']['ayahs'][1]['text'];
        // dd('com');
        // $image = $this->generatePng($text);
        return $this->sendResponse([$test], 'Get data successfully');
    }


    /////// create post.....///////
    public function addPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
            'type' => 'required',

        ]);
        if ($validator->fails()) {

            $errors = $this->sendError(implode(",", $validator->errors()->all()));
            throw new HttpResponseException($errors, 422);
        }
        $post = [
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type

        ];
        $data = Post::create($post);
        if (!$data) {
            return $this->sendError('Unable to proccess. Please try again later');
        }
        return $this->sendResponse([$data], 'Add Post successfully');
    }
    ///// get post/////////
    public function post(Request $request)
    {
        $post = Post::where('type', $request->type)->get();
        if (!$post) {
            return $this->sendError('Unable to proccess. Please try again later');
        }
        return $this->sendResponse([$post], 'get Post successfully');
    }
    /// our comunity post///////communityQoute//

    public function communityQoute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'description' => 'required',


        ]);
        if ($validator->fails()) {

            $errors = $this->sendError(implode(",", $validator->errors()->all()));
            throw new HttpResponseException($errors, 422);
        }

        $community = communityQoute::create([
            'user_id' => $request->user_id,
            'description' => $request->description
        ]);
        if (!$community) {
            return $this->sendError('Unable to proccess. Please try again later');
        }
        return $this->sendResponse([$community], 'Add Post successfully');
    }
    /// islamic post///////

    public function islamicQoute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'title' => 'required',
            'file' => 'required'


        ]);
        if ($validator->fails()) {

            $errors = $this->sendError(implode(",", $validator->errors()->all()));
            throw new HttpResponseException($errors, 422);
        }

        $islamic = islamicQoute::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'file' => $request->file
        ]);
        if (!$islamic) {
            return $this->sendError('Unable to proccess. Please try again later');
        }
        return $this->sendResponse([$islamic], 'Add Post successfully');
    }
    ///get post //////////
    public function getPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
        ]);
        if ($validator->fails()) {

            $errors = $this->sendError(implode(",", $validator->errors()->all()));
            throw new HttpResponseException($errors, 422);
        }
        if ($request->type == 'islamic') {
            $post = IslamicQoute::paginate(10);
        } else {

            $post = communityQoute::paginate(10);
        }
        return $this->sendResponse([$post], 'Get Post successfully');
    }

    /// prayer time zone with location/////
    public function prayerTime(PrayerTimeRequest $request)
    {
        // Get the user's latitude and longitude
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        // Send a request to the Islamic Finder API
        $url = 'https://api.aladhan.com/v1/timings?latitude=' . $latitude . '&longitude=' . $longitude . '&method=2';
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        if (!$data) {
            return $this->sendError('Unable to proccess. Please try again later');
        }

        // Get the prayer times from the API response
        $fajrTime = $data['data']['timings']['Fajr'];
        $dhuhrTime = $data['data']['timings']['Dhuhr'];
        $asrTime = $data['data']['timings']['Asr'];
        $maghribTime = $data['data']['timings']['Maghrib'];
        $ishaTime = $data['data']['timings']['Isha'];
        // Get the prayer times for the user's location

        // dd(auth()->user()->id);
        // Return the prayer times as a JSON response
        $responseData = [
            'user_id' => auth()->user()->id,
            'fajr_time' => $fajrTime,
            'dhuhr_time' => $dhuhrTime,
            'asr_time' => $asrTime,
            'maghrib_time' => $maghribTime,
            'isha_time' => $ishaTime,
        ];
        $location = [
            'user_id' => auth()->user()->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude

        ];

        $user_location = UserLocation::create($location);

        $currentTime = Carbon::now()->format('H:i');

        // $currentTime = getCurrentTimeByCoordinates($latitude, $longitude);

        // Print the current time
        // echo 'Current time: ' . $currentTime;
        // $timeZone = TimezoneMapper::getTzForGeoCoords($latitude, $longitude);

        // // Set the time zone
        // date_default_timezone_set($timeZone);

        // // Get the current time
        // $currentTime = date('Y-m-d H:i:s');

       
        
        // dd($currentTime);
        // dd($currentTime);
        $nextPrayerTime = '';
        if ($responseData) {
            if ($responseData['fajr_time'] >= $currentTime) {
                $nextPrayerTime = ['Fajr' => $responseData['fajr_time']];
            } else if ($responseData['dhuhr_time'] >= $currentTime) {
                $nextPrayerTime = ['Dhuhr' => $responseData['dhuhr_time']];
            } else if ($responseData['asr_time'] >= $currentTime) {
                $nextPrayerTime = ['Asr' => $responseData['asr_time']];
            } else if ($responseData['maghrib_time'] >= $currentTime) {
                $nextPrayerTime = ['Maghrib' => $responseData['maghrib_time']];
            } else if ($responseData['isha_time'] >= $currentTime) {
                $nextPrayerTime = ['Isha' => $responseData['isha_time']];
            }
        }


        
        // $today = Date::today();
        // $nextPrayerTime['date'] = $today->format('l d F o');

       

        return $this->sendResponse($nextPrayerTime, 'Get Prayer time successfully');
    }
}
