<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommonController extends Controller
{
    public function getCountries(Request $request)
    {
        try
        {
            $countries = Country::where('status', 1)->select('id', 'nice_name as name', 'phone_code')->orderBy('nice_name')->get();
                return response()->json(
                    [
                        'success' => true,
                        'status' => 200,
                        'message' =>  __('messages.country_success'),
                        'message_code' => 'user_login_success',
                        'data' => [
                           'countries' => $countries,
                        ],
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
