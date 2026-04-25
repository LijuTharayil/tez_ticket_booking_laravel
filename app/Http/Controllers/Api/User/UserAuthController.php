<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\OneTimePassword;
use App\Models\ReferenceLink;
use App\Models\TokenBonus;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserDevice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Mockery\Exception;
use Illuminate\Support\Facades\Auth;
use Image;

class UserAuthController extends Controller
{

    public function register(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'country_id'        => 'required|integer|exists:countries,id',
                'first_name'        => 'required|string|max:255',
                'last_name'         => 'required|string|max:255',
                'mobile_number'     => 'required|string|max:20|unique:users,mobile_number',
                'email'             => 'required|email|unique:users,email',
                'password'          => [
                    'required',
                    'confirmed',
                    'min:8',
                    'regex:/[a-z]/',      // lowercase
                    'regex:/[A-Z]/',      // uppercase
                    'regex:/[0-9]/',      // number
                    'regex:/[@$!%*#?&]/'  // special char
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => $validator->messages()->first(),
                    'message_code' => 'validation_error',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            // Create User
            $user = User::create([
                'user_unique_id' => generateUserUniqueId(),
                'country_id'     => $request->country_id,
                'first_name'     => $request->first_name,
                'last_name'      => $request->last_name,
                'mobile_number'  => $request->mobile_number,
                'email'          => $request->email,
                'password'       => Hash::make($request->password),
            ]);

            // Generate Passport Token
            $token = $user->createToken('authToken')->accessToken;
            $type = "Register";
            applyBonus($user, $type);
            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => '',
                'message_code' => 'register_success',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'user_unique_id' => $user->user_unique_id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'mobile_number' => $user->mobile_number,
                    ],
                    'token' => $token
                ],
                'errors' => [],
                'exception' => ''
            ], 200);

        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Oops!... Something Went Wrong',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $exception->getMessage()
            ], 200);
        }
    }

    public function login(Request $request)
    {
        try {
            DB::beginTransaction();

            $existingUser = User::where('email', $request->email)
                ->orWhere('mobile_number', $request->mobile_number)
                ->first();

            $validator = Validator::make($request->all(), [
                'country_id' => 'required|integer|exists:countries,id',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'mobile_number' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('users', 'mobile_number')->ignore(optional($existingUser)->id),
                ],
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users', 'email')->ignore(optional($existingUser)->id),
                ],
                'password' => [
                    'required',
                    'confirmed',
                    'min:8',
                    'regex:/[a-z]/',
                    'regex:/[A-Z]/',
                    'regex:/[0-9]/',
                    'regex:/[@$!%*#?&]/',
                ],
                'password_confirmation' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => $validator->messages()->first(),
                    'message_code' => 'validation_error',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            if ($existingUser) {
                if (!Hash::check($request->password, $existingUser->password)) {
                    return response()->json([
                        'success' => false,
                        'status' => 400,
                        'message' => 'Invalid credentials',
                        'message_code' => 'validation_error',
                        'data' => [],
                        'errors' => [],
                        'exception' => ''
                    ], 200);
                }

                $user = $existingUser;
            } else {
                do {
                    $userUniqueId = generateUserUniqueId();
                } while (User::where('user_unique_id', $userUniqueId)->exists());

                $user = User::create([
                    'user_unique_id' => $userUniqueId,
                    'country_id' => $request->country_id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'mobile_number' => $request->mobile_number,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);
            }

            $token = $user->createToken('authToken')->accessToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => '',
                'message_code' => 'login_success',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'user_unique_id' => $user->user_unique_id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'mobile_number' => $user->mobile_number,
                    ],
                    'token' => $token
                ],
                'errors' => [],
                'exception' => ''
            ], 200);

        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Oops!... Something Went Wrong',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $exception->getMessage()
            ], 200);
        }
    }
    public function userInfo(Request $request)
    {
        try
        {
            $time_zone = getLocalTimeZone($request->time_zone);
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => 'user_info_success',
                    'message_code' => 'user_info_success',
                    'data' => getUserData(auth()->id(), true),
                    'errors' => [],
                    'exception' => ''
                ], 200);
        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status' => 500,
                    'message' => 'Oops!... Something Went Wrong',
                    'message_code' => 'try_catch',
                    'data' => [],
                    'errors' => [],
                    'exception' => $exception->getMessage()
                ], 200);
        }

    }



    public function userLogout(Request $request)
    {
        try
        {
            Auth::user()->token()->revoke();
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => 'Successfully Logged Out',
                    'message_code' => 'user_logged_out_success',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status' => 500,
                    'message' => 'Oops!... Something Went Wrong',
                    'message_code' => 'try_catch',
                    'data' => [],
                    'errors' => [],
                    'exception' => $exception->getMessage()
                ], 200);
        }

    }

    public function userForgotPassword(Request $request)
    {
        try
        {
            DB::beginTransaction();
            $time_zone = getLocalTimeZone($request->time_zone);
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'nullable|integer',
                'email' => 'nullable|email',
            ]);
            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => $validator->messages()->first(),
                        'message_code' => 'validation_error',
                        'data' => [],
                        'errors' => [],
                        'exception' => ''
                    ], 200);
            }

            if((isset($request->mobile_number) && $request->mobile_number) && (isset($request->email) && $request->email))
            {
                $user = User::where('email', $request->email)->where('mobile_number', $request->mobile_number)->first();
                $medium = "EmailMobileNumber";
            }
            elseif((isset($request->mobile_number) && $request->mobile_number) && (!isset($request->email) || !$request->email))
            {
                $user = User::where('mobile_number', $request->mobile_number)->first();
                $medium = "MobileNumber";
            }
            elseif((!isset($request->mobile_number) || !$request->mobile_number) && (isset($request->email) && $request->email))
            {
                $user = User::where('email', $request->email)->first();
                $medium = "Email";
            }
            else
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => "Email Or Mobile Number Must Be Required",
                        'message_code' => 'validation_error',
                        'data' => [],
                        'errors' => [],
                        'exception' => ''
                    ], 200);
            }

            if(!$user)
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => "Email Or Mobile Number Does Not Exist",
                        'message_code' => 'user_validation_error',
                        'data' => [],
                        'errors' => [],
                        'exception' => ''
                    ], 200);
            }

            $otp_number = generateOTP();
            $expired_at_date_time_utc = Carbon::now('UTC')->addMinutes(5)->toDateTimeString();
            OneTimePassword::where('user_id', $user->id)->where('type', 'ForgotPassword')->delete();
            $one_time_password = new OneTimePassword();
            $one_time_password->user_id = $user->id;
            $one_time_password->type = "ForgotPassword";
            $one_time_password->medium = $medium;
            $one_time_password->otp_number = $otp_number;
            $one_time_password->expired_at = $expired_at_date_time_utc;
            $one_time_password->status = 1;
            $one_time_password->save();
            sendOtpNumber($user, $medium, $otp_number);
            DB::commit();
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => "OTP Send Successfully",
                    'message_code' => 'otp_send_success',
                    'data' => [
                        'medium' => $medium,
                        'expired_at_utc' => $expired_at_date_time_utc,
                        'expired_at_local' => getLocalTimeFromUtc($expired_at_date_time_utc, $time_zone),
                    ],
                    'errors' => [],
                    'exception' => ''
                ], 200);

        }
        catch (\Exception $exception)
        {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'status' => 500,
                    'message' => 'Oops!... Something Went Wrong',
                    'message_code' => 'try_catch',
                    'data' => [],
                    'errors' => [],
                    'exception' => $exception->getMessage()
                ], 200);
        }

    }

    public function userForgotPasswordVerifyOtp(Request $request)
    {
        try
        {
            DB::beginTransaction();
            $time_zone = getLocalTimeZone($request->time_zone);
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'nullable|integer',
                'email' => 'nullable|email',
                'otp_number' => 'integer|min:1000|max:9999',
            ]);
            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => $validator->messages()->first(),
                        'message_code' => 'validation_error',
                        'data' => [],
                        'errors' => [],
                        'exception' => ''
                    ], 200);
            }

            if((isset($request->mobile_number) && $request->mobile_number) && (isset($request->email) && $request->email))
            {
                $user = User::where('email', $request->email)->where('mobile_number', $request->mobile_number)->first();
                $medium = "EmailMobileNumber";
            }
            elseif((isset($request->mobile_number) && $request->mobile_number) && (!isset($request->email) || !$request->email))
            {
                $user = User::where('mobile_number', $request->mobile_number)->first();
                $medium = "MobileNumber";
            }
            elseif((!isset($request->mobile_number) || !$request->mobile_number) && (isset($request->email) && $request->email))
            {
                $user = User::where('email', $request->email)->first();
                $medium = "Email";
            }
            else
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => "Email Or Mobile Number Must Be Required",
                        'message_code' => 'validation_error',
                        'data' => [],
                        'errors' => [],
                        'exception' => ''
                    ], 200);
            }

            if(!$user)
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => "Email Or Mobile Number Does Not Exist",
                        'message_code' => 'user_validation_error',
                        'data' => [],
                        'errors' => [],
                        'exception' => ''
                    ], 200);
            }

            $one_time_password = OneTimePassword::where('user_id', $user->id)->where('type', 'ForgotPassword')->where('status', 1)->where('otp_number', $request->otp_number)->first();
            if($one_time_password)
            {
                $current_date_time_utc = Carbon::now('UTC')->toDateTimeString();
                if($current_date_time_utc > $one_time_password->expired_at)
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status' => 400,
                            'message' => "OTP Expired",
                            'message_code' => 'otp_date_validation_error',
                            'data' => [],
                            'errors' => [],
                            'exception' => ''
                        ], 200);
                }
                $token = $one_time_password->id."-".Str::random(12);
                OneTimePassword::where('id', $one_time_password->id)->update(['token' => $token, 'status' => 0]);
            }
            else
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => "OTP Does Not Match",
                        'message_code' => 'otp_validation_error',
                        'data' => [],
                        'errors' => [],
                        'exception' => ''
                    ], 200);
            }
            DB::commit();
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => "OTP Verified Successfully",
                    'message_code' => 'otp_verify_success',
                    'data' => [
                        'token' => $token
                    ],
                    'errors' => [],
                    'exception' => ''
                ], 200);

        }
        catch (\Exception $exception)
        {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'status' => 500,
                    'message' => 'Oops!... Something Went Wrong',
                    'message_code' => 'try_catch',
                    'data' => [],
                    'errors' => [],
                    'exception' => $exception->getMessage()
                ], 200);
        }

    }

    public function userForgotResetPassword(Request $request)
    {
        try
        {
            DB::beginTransaction();
            $time_zone = getLocalTimeZone($request->time_zone);
            $validator = Validator::make($request->all(), [
                'token' => 'required',
                'password' => 'required|string|min:6|confirmed',

            ]);
            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => $validator->messages()->first(),
                        'message_code' => 'validation_error',
                        'data' => [],
                        'errors' => [],
                        'exception' => ''
                    ], 200);
            }

            $one_time_password = OneTimePassword::where('type', 'ForgotPassword')->where('status', 0)->where('token', $request->token)->first();
            if($one_time_password)
            {
                $user = User::where('id', $one_time_password->user_id)->first();
                if(Hash::check($request->password, $user->password))
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status' => 400,
                            'message' => "Current Password Not Use As New Password",
                            'message_code' => 'same_password',
                            'data' => [],
                            'errors' => [],
                            'exception' => ''
                        ], 200);
                }

                User::where('id', $user->id)->update(['password' => bcrypt($request->password)]);
                $one_time_password->delete();
            }
            else
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => "Invalid Token",
                        'message_code' => 'token_validation_error',
                        'data' => [],
                        'errors' => [],
                        'exception' => ''
                    ], 200);
            }
            DB::commit();
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => "Password Successfully Updated",
                    'message_code' => 'password_reset_success',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);

        }
        catch (\Exception $exception)
        {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'status' => 500,
                    'message' => 'Oops!... Something Went Wrong',
                    'message_code' => 'try_catch',
                    'data' => [],
                    'errors' => [],
                    'exception' => $exception->getMessage()
                ], 200);
        }

    }

}
