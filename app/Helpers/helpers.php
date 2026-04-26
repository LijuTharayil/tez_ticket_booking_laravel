<?php
use App\Models\Localization;
use App\Models\Prediction;
use App\Models\ReferenceLink;
use App\Models\CommissionLevel;
use App\Models\TokenBonus;
use App\Models\User;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\Score;
use App\Models\UserSocialMedia;
use App\Models\UserTicket;
use App\Models\WithdrawableBalance;
use App\Models\RankLevel;
use App\Models\UserSplash;
use App\Models\ScoreBurn;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


function testHelperFunction()
{
    return "Test Helper Function Successfully";
}
function getPerPageLimit($per_page)
{
    try
    {
        if($per_page == "" || $per_page == null)
        {
            return 10;
        }
        return $per_page;
    }
    catch (\Exception $exception)
    {
        return 10;
    }

}

function getLocalTimeFromUtc($utc_time, $local_time_zone = "Asia/Kolkata")
{
    try
    {
        // $utc_time must be in y-m-d H:i:s Format
        // $local_time_zone in "Asia/Kolkata" format
        if($utc_time == NULL) return "";
        if(!$local_time_zone) $local_time_zone = "Asia/Kolkata";
        $given_utc_time  = Carbon::createFromFormat('Y-m-d H:i:s', $utc_time, 'UTC');
        return $given_utc_time->setTimezone($local_time_zone)->toDateTimeString();
    }
    catch (\Exception $exception)
    {
        return "";
    }
}

function getReadableLocalTimeFromUtc($utc_time, $local_time_zone = "Asia/Kolkata")
{
    try
    {
        // $utc_time must be in y-m-d H:i:s Format
        // $local_time_zone in "Asia/Kolkata" format
        if($utc_time == NULL) return "";
        if(!$local_time_zone) $local_time_zone = "Asia/Kolkata";
        $given_utc_time  = Carbon::createFromFormat('Y-m-d H:i:s', $utc_time, 'UTC');
        return $given_utc_time->setTimezone($local_time_zone)->format('d-m-Y h:i A');
    }
    catch (\Exception $exception)
    {
        return "";
    }
}

function getReadableDate($date)
{
    try
    {
        if($date)
        {
            return date('d-m-Y', strtotime($date));
        }
        else
        {
            return null;
        }
    }
    catch (\Exception $exception)
    {
        return null;
    }
}

function getUtcTimeFromLocal($local_time, $local_time_zone = "Asia/Kolkata")
{
    try
    {
        // $local_time must be in y-m-d H:i:s Format
        // $local_time_zone in "Asia/Kolkata" format
        if($local_time == NULL) return "";
        if(!$local_time_zone) $local_time_zone = "Asia/Kolkata";
        $given_local_time  = Carbon::createFromFormat('Y-m-d H:i:s', $local_time, $local_time_zone);
        return $given_local_time->setTimezone('UTC')->toDateTimeString();
    }
    catch (\Exception $exception)
    {
        return "";
    }
}

function getLocalTimeZone($local_time_zone = "Asia/Kolkata")
{
     try
     {
         if(!$local_time_zone) $local_time_zone = "Asia/Kolkata";
         return $local_time_zone;
     }
     catch (\Exception $exception)
     {
         return $local_time_zone;
     }
}

function getDbDateTimeFormat($date_time)
{
    try
    {
        if($date_time)
        {
            return date('Y-m-d H:i:s', strtotime($date_time));
        }
        else
        {
            return null;
        }
     }
    catch (\Exception $exception)
    {
        return null;
    }

}

function getDbDateFormat($date)
{
    try
    {
        if($date)
        {
            return date('Y-m-d', strtotime($date));
        }
        else
        {
            return null;
        }
    }
    catch (\Exception $exception)
    {
        return null;
    }
}

function generateUserUniqueId()
{
    try
    {
        // generate alphabet string
        $alphabet_length = 1;
        $alphabet_characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $alphabet_characters_length = strlen($alphabet_characters);
        $alphabet_random_string = '';
        $alphabet_random_string .= $alphabet_characters[rand(0, $alphabet_characters_length - 1)];

        // generate number string
        $number_length = 1;
        $number_characters = '1234567890';
        $number_characters_length = strlen($number_characters);
        $number_random_string = '';
        $number_random_string .= $number_characters[rand(0, $number_characters_length - 1)];

        // generate mixed string
        $mixed_length = 4;
        $mixed_characters = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $mixed_characters_length = strlen($mixed_characters);
        $mixed_random_string = '';
        for ($i = 0; $i < $mixed_length; $i++)
        {
            $mixed_random_string .= $mixed_characters[rand(0, $mixed_characters_length - 1)];
        }
        // concatenate number, alphabet with random mixed string
        $final_string = $mixed_random_string.$alphabet_random_string.$number_random_string;
        // shuffle the final string
        $reference_key = str_shuffle($final_string);

        return $reference_key;
    }
    catch (\Exception $exception)
    {
        return null;
    }

}


function sendOtpNumber($user, $medium, $otp_number)
{
    try
    {
        return true;
    }
    catch (\Exception $exception)
    {
        return false;
    }
}

function generateOTP()
{
    try
    {
        if(env('APP_ENV') == 'local' || env('APP_ENV') == 'development' || env('APP_ENV') == 'dev' || env('APP_ENV') == 'test')
        {
            $otp_number = 2255;
        }
        else
        {
            $otp_number = rand(1000, 9999);
        }
        return $otp_number;
    }
    catch (\Exception $exception)
    {
        return 2255;
    }

}

function getUserData($user_id, $token_status = false)
{
    try
    {
        $user = User::where('id', $user_id)->first();
        $total_score = $user->total_score;
        $total_tokens = $user->total_tokens;
        $userRank = User::orderByDesc('total_score')->pluck('id')->search($user_id) + 1;
       
    
        return [
            'id' => $user->id,
            'user_unique_id' => (string) $user->user_unique_id,
            'first_name' => (string) $user->first_name,
            'last_name' => (string) $user->last_name,
            'user_name' => (string) $user->user_name,
            'email' => (string) $user->email,
            'mobile_number' => (string) $user->mobile_number,
            'wallet_address' => (string) $user->wallet_address,
            'token' => ($token_status)?$user->createToken('TicketBooking')->accessToken:""
        ];
    }
    catch (\Exception $exception)
    {
        return [];
    }

}

function getMetaData($data)
{
    if (!$data) {
        return [];
    }

    return [
        'total_pages' => (int) $data->lastPage(),
        'current_page' => (int) $data->currentPage(),
        'total_records' => (int) $data->total(),
        'records_on_current_page' => (int) $data->count(),
        'record_from' => $data->firstItem() ? (int) $data->firstItem() : 0,
        'record_to' => $data->lastItem() ? (int) $data->lastItem() : 0,
        'records_per_page' => (int) $data->perPage(),
    ];
}
function getDefaultPerPage($module = null)
{
    return 10;
}


function applyBonus($user, $type, $referenceId = null, $referenceModel = null)
{
    try {

        $bonus = TokenBonus::where('type', $type)->first();

        if ($bonus && $bonus->token_qty > 0) {

            if($type == 'PredictBonus')
            {
                $alreadyCredited = Prediction::where('user_id', $user->id)
                    ->where('question_id', $referenceId)
                    ->exists();
            } 
            else 
            {
                $alreadyCredited = Transaction::where('user_id', $user->id)
                ->where('type', $type)
                ->where('account_type', 'Credit')
                ->exists();
            }
            
            if (!$alreadyCredited) 
            {
                return createTransaction($user, $type, 'Credit', $bonus->token_qty, $referenceId, $referenceModel);
            }
        }

        return false;

    } catch (\Exception $e) {
        return false;
    }
}

function createTransaction($user, $type, $accountType, $amount, $referenceId = null, $referenceModel = null)
{
    DB::beginTransaction();

    try {
        $transaction = Transaction::create([
            'transaction_id' => uniqid('TXN-'),
            'type' => $type,
            'account_type' => $accountType,
            'token_quantity' => $amount,
            'transaction_on' => now(),
            'user_id' => $user->id,
            'reference_id' => $referenceId,
            'reference_model' => $referenceModel,
        ]);

        if ($accountType === 'Credit') {
            $user->increment('total_token_credit', $amount);
            $user->increment('total_token_balance', $amount);
        } else {
            $user->increment('total_token_debit', $amount);
            $user->decrement('total_token_balance', $amount);
        }

        DB::commit();
        return $transaction;

    } catch (\Exception $e) {
        DB::rollBack();
        return null;
    }
}

function getUserProfileDetails($userId)
{
    try 
    {
        $user = User::find($userId);

        if (!$user) {
            return null;
        }

        $socialMedia = UserSocialMedia::where('user_id', $user->id)->get();

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'mobile' => $user->mobile_number,

            'is_email_verified' => (bool)$user->is_email_verified,
            'is_mobile_verified' => (bool)$user->is_eis_mobile_verifiedmail_verified,

            'total_token_credit' => (string)$user->total_token_credit,
            'total_token_debit' => (string)$user->total_token_debit,
            'total_token_balance' => (string)$user->total_token_balance,

            'social_media' => $socialMedia->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'url' => $item->url,
                    'is_approved' => (bool)$item->is_approved,
                    'approved_by_admin_id' => $item->approved_by_admin_id,
                    'approved_on' => $item->approved_on,
                ];
            })->values()
        ];
    } 
    catch (\Exception $e) 
    {
        return $e;
        return null;
    }
}

function generateBookingReference()
{
    do {
        $code = 'BK' . strtoupper(Str::random(16)); // e.g., BK9X2AB7CD
    } while (UserTicket::where('booking_reference', $code)->exists());

    return $code;
}