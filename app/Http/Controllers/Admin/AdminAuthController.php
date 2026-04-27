<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    public function adminLogin(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => $validator->messages()->first(),
                        'message_code' => 'validation_error',
                        'data' => [],
                        'errors' => $validator->errors()->all(),
                        'exception' => ''
                    ], 200);
            }

            if (isset($request->remember) && $request->remember == true) {
                $remember = true;
            } else {
                $remember = false;
            }

            $admin = Admin::where('email', $request->email)->first();

            // Check if admin exists and password is correct
            if (!$admin || !Hash::check($request->password, $admin->password)) {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => 'Invalid Credentials',
                        'message_code' => 'invalid_credentials',
                        'data' => [],
                        'errors' => [],
                        'exception' => ''
                    ], 200);
            }

            if ($admin)
            {
                return response()->json(
                    [
                        'success' => true,
                        'status' => 200,
                        'message' =>  'Logged In Success',
                        'message_code' => 'admin_login_success',
                        'data' => [
                            'first_name' => $admin->first_name,
                            'last_name' => $admin->last_name,
                            'email' => $admin->email,
                            'role' => $admin->role,
                            'token' => $admin->createToken('TicketAdmin')->accessToken,
                        ],
                        'errors' => [],
                        'exception' => ''
                    ], 200);
            } else {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => 'Invalid Credentials',
                        'message_code' => 'invalid_credentials',
                        'data' => [],
                        'errors' => [],
                        'exception' => ''
                    ], 200);
            }
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

    public function adminLogout(Request $request) {
        try
        {
           auth('admin')->logout();
           return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Successfully Logged Out',
            'message_code' => 'admin_logged_out_success',
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
}
