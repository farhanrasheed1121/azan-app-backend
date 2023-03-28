<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\SignupRequest;
use App\Http\Traits\ResponseTrait;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ResponseTrait;
    /////////  user register.........///////
    public function signUp(SignupRequest $request)
    {
        $UserData = [
            'name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'user_name' => $request->user_name,
            'password' => bcrypt($request->password),
            'country' => $request->country,
            'city' => $request->city
        ];

        $registeredUser = User::create($UserData);
        if (!$registeredUser) {
            return $this->sendError('User has not registered. Please try again later');
        }

        $userData  = User::find($registeredUser->id);
        $userData->token = $registeredUser->createToken('API Token')->accessToken;
        return $this->sendResponse($userData, 'User Registered Successfully!');
    }
    ///////........ login user..........////////
    public function login(LoginRequest $request)
    {
        $UserData = [
            'phone_number' => $request->phone_number,
            'password' => $request->password,
        ];
        if (!auth()->attempt($UserData)) {
            return $this->sendError('Try again. Wrong password.Try again or click forget password to reset your password.');
        }
        $authUser = auth()->user();
        $authUser->token = $authUser->createToken('API Token')->accessToken;
        return $this->sendResponse($authUser, 'Login Successful!');
    }
    //////// send otp....//////
    public function sendOtp(SendOtpRequest $request)
    {
        $otp = rand(1000, 9999);
        if (!User::where('phone_number', $request->phone_number)->update(['otp_code' => $otp])) {
            return $this->sendError('Unable to proccess. Please try again later');
        }
        // Mail::to($request->email)->send(new VerifyEmail($otp));
        return $this->sendResponse([['otp_code' => $otp]], 'Otp code sent to your email');
    }
}
