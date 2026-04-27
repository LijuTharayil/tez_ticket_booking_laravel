<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSocialMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
{
    public function getUsers(Request $request)
    {
        try {
            $query = User::with('social_medias'); // 🔥 eager load

            // 🔍 Filters
            if ($request->filled('search')) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")
                    ->orWhere('last_name', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%")
                    ->orWhere('mobile_number', 'LIKE', "%$search%")
                    ->orWhere('user_unique_id', 'LIKE', "%$search%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $perPage = $request->per_page ?? 10;

            $users = $query->latest()->paginate($perPage);

            $data = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'user_unique_id' => $user->user_unique_id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'mobile_number' => $user->mobile_number,

                    'wallet' => [
                        'total_credit' => (string) $user->total_token_credit,
                        'total_debit' => (string) $user->total_token_debit,
                        'balance' => (string) $user->total_token_balance,
                    ],

                    'is_email_verified' => (bool) $user->is_email_verified,
                    'is_mobile_verified' => (bool) $user->is_mobile_verified,

                    // 🔥 Social Media
                    'social_medias' => $user->social_medias->map(function ($social) {
                        return [
                            'id' => $social->id,
                            'type' => $social->type,
                            'url' => $social->url,
                            'is_approved' => (bool) $social->is_approved,
                            'approved_on' => $social->approved_on,
                        ];
                    }),

                    'created_at' => $user->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Users fetched successfully',
                'message_code' => 'user_list_success',
                'data' => $data,
                'meta_data' => getMetaData($users),
                'errors' => [],
                'exception' => ''
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Oops!... Something Went Wrong',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $e->getMessage()
            ], 200);
        }
    }

    public function approveSocialMedia(Request $request)
    {
        try {

            $admin = auth('admin')->user();

            // ✅ Validation
            $validator = Validator::make($request->all(), [
                'user_social_media_id' => 'required|exists:user_social_media,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => $validator->messages()->first(),
                    'message_code' => 'validation_error',
                    'data' => [],
                    'errors' => $validator->errors()->all(),
                    'exception' => ''
                ], 200);
            }

            // ✅ Get Social Media Record
            $social = UserSocialMedia::with('user')->find($request->user_social_media_id);

            if (!$social) {
                return response()->json([
                    'success' => false,
                    'status' => 404,
                    'message' => 'Social media not found',
                    'message_code' => 'not_found',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            // ✅ Prevent double approval
            if ($social->is_approved) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'Already Approved',
                    'message_code' => 'already_approved',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            // ✅ Approve
            $social->update([
                'is_approved' => 1,
                'approved_by_admin_id' => $admin->id,
                'approved_on' => now(),
            ]);

            // ✅ Apply Bonus (ONLY ONCE)
            $user = $social->user;

            if ($user) {

                $alreadyCredited = Transaction::where('user_id', $user->id)
                    ->where('type', 'SocialMediaBonus')
                    ->where('reference_id', $social->id)
                    ->where('reference_model', 'UserSocialMedia')
                    ->where('account_type', 'Credit')
                    ->exists();

                if (!$alreadyCredited) {

                    applyBonus(
                        $user,
                        'SocialMediaBonus',
                        $social->id,
                        'UserSocialMedia'
                    );
                }
            }

            // ✅ Response
            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Approved Successfully',
                'message_code' => 'social_media_approved',
                'data' => [
                    'id' => $social->id,
                    'type' => $social->type,
                    'url' => $social->url,
                    'is_approved' => true
                ],
                'errors' => [],
                'exception' => ''
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Oops!... Something Went Wrong',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $e->getMessage()
            ], 200);
        }
    }
}
