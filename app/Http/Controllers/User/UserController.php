<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrayerTimeRequest;
use App\Http\Traits\ResponseTrait;
use App\Models\Azkar;
use App\Models\AzkarContent;
use App\Models\CommunityQoute;
use App\Models\GuestUser;
use App\Models\IslamicQoute;
use App\Models\Post;
use App\Models\PrayerTime;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Tasbih;
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
            'latitude' => 'required',
            'longitude' => 'required',
            'current_time' => 'required'
        ]);
        if ($validator->fails()) {

            $errors = $this->sendError(implode(",", $validator->errors()->all()));
            throw new HttpResponseException($errors, 422);
        }
        if ($request->type == 'islamic') {
            $post = IslamicQoute::with('user')->paginate(10);
        } else {

            $post = communityQoute::with('user')->paginate(10);
        }
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $currentTime = $request->current_time;

        $prayer = $this->prayerTime($latitude, $longitude, $currentTime);

        return $this->sendResponse([$post, $prayer], 'Get Post successfully');
    }

    /// prayer time zone with location/////
    public function prayerTime($latitude, $longitude, $currentTime)
    {
        // $timestamp = Carbon::now()->timestamp;

        // // Get timezone at user's location
        // $timezone = timezone_name_from_latitude_and_longitude($latitude, $longitude);

        // // Convert to user's local time
        // $userTime = Carbon::createFromTimestamp($timestamp, $timezone);

        // // Format as desired
        // $userTimeFormatted = $userTime->format('H:i');
        // dd($userTimeFormatted);
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

            'fajr_time' => $fajrTime,
            'dhuhr_time' => $dhuhrTime,
            'asr_time' => $asrTime,
            'maghrib_time' => $maghribTime,
            'isha_time' => $ishaTime,
        ];
        // $location = [
        //     // 'user_id' => auth()->user()->id,
        //     'latitude' => $request->latitude,
        //     'longitude' => $request->longitude

        // ];

        // $user_location = UserLocation::create($location);

        $currentTime = Carbon::now()->format('H:i');




        // dd($currentTime);

        $nextPrayerTime = '';
        if ($responseData) {
            if ($responseData['fajr_time'] >= $currentTime) {
                $nextPrayerTime = [
                    'title' => 'Fajr',
                    'time' => $responseData['fajr_time']
                ];
            } else if ($responseData['dhuhr_time'] >= $currentTime) {
                $nextPrayerTime = [
                    'title' => 'Dhuhr',
                    'time' => $responseData['dhuhr_time']
                ];
            } else if ($responseData['asr_time'] >= $currentTime) {
                $nextPrayerTime = [
                    'title' => 'Asr',
                    'time' => $responseData['asr_time']
                ];
            } else if ($responseData['maghrib_time'] >= $currentTime) {
                $nextPrayerTime = [
                    'title' => 'Maghrib',
                    'time' => $responseData['maghrib_time']
                ];
            } else if ($responseData['isha_time'] >= $currentTime) {
                $nextPrayerTime = [
                    'title' => 'Isha',
                    'time' => $responseData['isha_time']
                ];
            } else if ($responseData['isha_time'] <= $currentTime) {
                $nextPrayerTime = [
                    'title' => 'Fajr',
                    'time' => $responseData['fajr_time']
                ];
            }
        }



        // $today = Date::today();
        // $nextPrayerTime['date'] = $today->format('l d F o');



        return $this->sendResponse([
            'prayers' => $responseData,
            'upcomming_prayer' => $nextPrayerTime,

        ], 'Get Prayer time successfully');
    }

    ////// add Question....//////
    public function question(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required',
            'option' => 'required',

        ]);
        if ($validator->fails()) {

            $errors = $this->sendError(implode(",", $validator->errors()->all()));
            throw new HttpResponseException($errors, 422);
        }
        $question = Question::create([
            'user_id' => auth()->user()->id,
            'question' => $request->question,

        ]);
        if (!$question) {
            return $this->sendError('Unable to proccess. Please try again later');
        }
        $option = $request->option;
        foreach ($option as $options) {
            $question_option = new QuestionOption();
            $question_option->question_id = $question->id;
            $question_option->title = $options['title'];
            $question_option->status = $options['status'];
            $question_option->save();
        }
        // $question['option'] = $question_option;
        return $this->sendResponse([$question], 'Question set Successfully');
    }

    ////////get question ////////

    public function getQuestion()
    {
        $question = Question::with('option')->get();
        if (!$question) {
            return $this->sendError('Unable to proccess. Please try again later');
        }
        return $this->sendResponse($question, 'Question get Successfully');
    }

    //////// add Azkar /////////////
    public function addAzkar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'content' => 'required',

        ]);
        if ($validator->fails()) {

            $errors = $this->sendError(implode(",", $validator->errors()->all()));
            throw new HttpResponseException($errors, 422);
        }
        $azkar = Azkar::create([
            'title' => $request->title
        ]);
        if (!$azkar) {
            return $this->sendError('Unable to proccess. Please try again later');
        }
        $content = $request->content;
        foreach ($content as $contents) {
            $azkar_content = new AzkarContent();
            $azkar_content->azkar_id = $azkar->id;
            $azkar_content->content = $contents['content'];
            $azkar_content->save();
        }
        return $this->sendResponse([$azkar], 'Question set Successfully');
    }

    ///get azkar //
    public function getAzkar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required||exists:azkar_contents,azkar_id',
        ]);
        if ($validator->fails()) {

            $errors = $this->sendError(implode(",", $validator->errors()->all()));
            throw new HttpResponseException($errors, 422);
        }
        $azkar = AzkarContent::where('azkar_id', $request->id)->get();
        if (!$azkar) {
            return $this->sendError('Unable to proccess. Please try again later');
        }
        return $this->sendResponse($azkar, 'Question get Successfully');
    }
    //////// add tasbih  //////////
    public function addTasbih(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'content' => 'required',
            'count' => 'required',
        ]);
        if ($validator->fails()) {

            $errors = $this->sendError(implode(",", $validator->errors()->all()));
            throw new HttpResponseException($errors, 422);
        }
        $tasbih = Tasbih::create([
            'title' => $request->title,
            'content' => $request->content,
            'count' => $request->count,
        ]);
        if (!$tasbih) {
            return $this->sendError('Unable to proccess. Please try again later');
        }
        return $this->sendResponse([$tasbih], 'Tasbih set Successfully');
    }
    //// get tasbih ///////////
    public function getTasbih()
    {
        $tasbih = Tasbih::get();
        if (!$tasbih) {
            return $this->sendError('Unable to proccess. Please try again later');
        }
        return $this->sendResponse($tasbih, 'Tasbih get Successfully');
    }
    /// guest user...........///////
    public function guestUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'time_zone' => 'required',
        ]);
        if ($validator->fails()) {

            $errors = $this->sendError(implode(",", $validator->errors()->all()));
            throw new HttpResponseException($errors, 422);
        }
        // dd('com');
        $data = $request->all();
        $guestuser = GuestUser::updateOrCreate(
            [
                'device_id' => $request->device_id
            ],
            $data
        );
        if (!$guestuser) {
            return $this->sendError('Unable to proccess. Please try again later');
        }
        return $this->sendResponse($guestuser, 'Guest User insert Successfully');
    }
}
