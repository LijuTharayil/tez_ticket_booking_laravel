<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\RankLevel;
use App\Models\Score;
use App\Models\User;
use App\Models\UserSplash;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use function getRank;

class UserHomeController extends Controller
{
    public function getCountries()
    {
        DB::beginTransaction();

        try {
            $countries = Country::select('id', 'iso', 'name', 'nice_name', 'iso3', 'number_code', 'phone_code')
                ->where('status', 1)
                ->orderBy('nice_name', 'asc')
                ->get();

            if ($countries->isEmpty()) {
                DB::commit();

                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No countries found',
                    'message_code' => 'country_not_found',
                    'data' => [],
                    'errors' => [],
                    'exception' => '',
                ], 200);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => '',
                'message_code' => 'country_list_success',
                'data' => [
                    'countries' => $countries,
                ],
                'errors' => [],
                'exception' => '',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Oops!... Something Went Wrong',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $e->getMessage(),
            ], 200);
        }
    }
}
