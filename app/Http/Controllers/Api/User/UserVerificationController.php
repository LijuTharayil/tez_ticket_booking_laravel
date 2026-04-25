<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\OneTimePassword;
use App\Models\TokenBonus;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserVerificationController extends Controller
{
    public function sendOtp(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();

            $otp = generateOTP();

            OneTimePassword::create([
                'user_id' => $user->id,
                'type' => $request->type, // email/mobile
                'medium' => $request->type,
                'otp_number' => $otp,
                'expired_at' => now()->addMinutes(5)
            ]);

            sendOtpNumber($user, $request->type, $otp);

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'OTP sent successfully',
                'message_code' => 'otp_sent',
                'data' => [],
                'errors' => [],
                'exception' => ''
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $e->getMessage()
            ], 200);
        }
    }

    public function verifyOtp(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();

            $otp = OneTimePassword::where('user_id', $user->id)
                ->where('otp_number', $request->otp)
                ->where('medium', $request->type)
                ->where('status', 1)
                ->first();

            if (!$otp) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'Invalid OTP',
                    'message_code' => 'invalid_otp',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            if (now() > $otp->expired_at) {
                $otp->delete();
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'OTP expired',
                    'message_code' => 'otp_expired',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            $otp->status = 0;
            $otp->save();

            // Update verification
            if ($request->type == 'email') {
                $user->is_email_verified = 1;
                $user->email_verified_at = now();
                $type = 'EmailVerify';
            } else {
                $user->is_mobile_verified = 1;
                $user->mobile_verified_at = now();
                $type = 'MobileVerify';
            }
            $user->save();
            // Give bonus
           applyBonus($user, $type);
            $otp->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Verified successfully',
                'message_code' => 'verification_success',
                'data' => [],
                'errors' => [],
                'exception' => ''
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $e->getMessage()
            ], 200);
        }
    }

    public function resendOtp(Request $request)
    {
        return $this->sendOtp($request);
    }
}
