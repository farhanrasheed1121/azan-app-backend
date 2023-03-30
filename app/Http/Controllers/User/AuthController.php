<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\SignupRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Traits\ResponseTrait;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


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

    // Verification of OTP Code API 
    public function verifyOTP(VerifyOtpRequest $request)
    {

        $user = User::where([['phone_number', '=', $request->phone_number], ['otp_code', '=', $request->otp_code]])->exists();
        if (!$user) {
            return $this->sendError('Invalid Code.');
        }
        return $this->sendResponse([], 'Otp matched, Change your password.');
    }

    //////////update password....../////////
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|confirmed|min:8',
            'phone_number' => 'required|exists:users,phone_number'
        ]);
        if ($validator->fails()) {
            return $this->sendError('Password should not be less than 8 digits and must match.', $validator->errors());
        }

        if (!User::where('phone_number', $request->phone_number)->update(['otp_code' => Null, 'password' => bcrypt($request->password)])) {
            return $this->sendError('Unable to process. Please try again later.');
        }
        return $this->sendResponse([], 'Password updated successfully.');
    }
}
