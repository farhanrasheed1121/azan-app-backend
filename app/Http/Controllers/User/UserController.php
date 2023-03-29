<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ResponseTrait;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Intervention\Image\Facades\Image;


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
        $image = $this->generatePng($text);
        return $this->sendResponse([[$image]], 'Get data successfully');
    }

    public function generatePng($text)
    {

        $img = Image::canvas(400, 200, '#ffffff');
        $img->text($text, 200, 100, function ($font) {
            $font->file(public_path('fonts/arial.ttf'));
            $font->size(48);
            $font->color('#000000');
            $font->align('center');
            $font->valign('middle');
        });
        $png = $img->encode('png');
        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="text.png"'
        ]);
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
        return $this->sendResponse([[$data]], 'Add Post successfully');
    }
}
