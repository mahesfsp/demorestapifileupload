<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\FcmToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{




    public function signIn(Request $request)
    {
       
        $user = User::where('email', '=', $request->email)->first();

        $isAuthorized = false;
        $user = User::where('email', '=', $request->email)->first();
        if ($user)
            $isAuthorized = true;


        if (!$isAuthorized) {
            return response()->json([
                'message' => [
                    'message' => __('staticwords.Unauthorized'),
                    'type' => 'error'
                ]
            ], 401);
        } else {

            return response()->json([
                'data' => [
                    'token' => $user->createToken('authToken')->accessToken,
                    'id' => $user->id,
                    'email' =>  $user->email,
                    'createdAt' => $user->created_at,
                    'updatedAt' => $user->updated_at
                ]
            ]);
        }
    }

    public function socialLogin(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'provider_name' => 'required',
            'access_token' => 'required',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('staticwords.Bad Request'),
                'type' => 'error'
            ], 400);
        }
        $provider = $request->provider_name; //  for multiple providers
        $token = $request->access_token;


        // get the provider's user. (In the provider server)
        //$providerUser = Socialite::driver($provider)->userFromToken($token);

        // if(!$providerUser ){
        //     return response()->json([
        //         'message' => [
        //             'message' => 'Invalid Credential',
        //             'type' => 'error'
        //         ]
        //     ], 401);
        // }

        $providerUser = null;

        try {

            //$providerUser = Socialite::driver($provider)->stateless()->userFromToken($token);
            $providerUser = Socialite::driver($provider)->userFromToken($token);
        } catch (Exception $exception) {
            return response()->json([
                'message' => __('staticwords.Invalid Credential'),
                'type' => 'error'
            ], 401);
        }


        // search for a user in our server with the specified provider id and provider name
        $user = User::where('provider_name', $provider)->where('provider_id', $providerUser->id)->first();
        // if there is no record with these data, create a new user
        if ($user == null) {
            $user = User::create([
                //'name'          => $userSocial->getName() ?: 'Social User',
                //'email'         => $userSocial->getEmail(),
                //'image'         => $userSocial->getAvatar(),
                //'name'           => $providerUser->name,
                //'email'          => $providerUser->email,
                'provider_name'  => $provider,
                'provider_id'     => $providerUser->id,
            ]);
        }
        // create a token for the user, so they can login
        //$token = $user->createToken(env('APP_NAME'))->accessToken;
        $token = $user->createToken('authToken')->accessToken;
        //  $token =$user->createToken('authToken')->plainTextToken;
        // return the token for usage
        return response()->json([
            'success' => true,
            'token' => $token
        ]);
    }


    public function isSignedIn(Request $request)
    {
        if ($request->uuid) {
            return response()->json([
                'data' => [
                    'isSignedIn' => $request->user()->uuid == $request->uuid ? true : false
                ]
            ]);
        } else if ($request->mobileNumber) {
            return response()->json([
                'data' => [
                    'isSignedIn' => ((int)$request->user()->mobile_number) == $request->mobileNumber ? true : false
                ]
            ]);
        } else {
            return response()->json([
                'message' => [
                    'message' => __('staticwords.Either uuid or mobile number required'),
                    'type' => 'error'
                ]
            ]);
        }
    }
}
