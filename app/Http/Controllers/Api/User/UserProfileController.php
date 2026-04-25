<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSocialMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Image;

class UserProfileController extends Controller
{

    public function getProfile(Request $request)
    {
        $user = auth()->user();
        return response()->json([
            'status' => true,
            'message' => 'Profile fetched successfully',
            'data' => getUserProfileDetails($user->id)
        ]);
    }
    public function updateSocialMedia(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'type' => 'required|in:Instagram,Facebook,Twitter,Telegram,LinkedIn',
                'url' => 'required|url'
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

            $user = auth()->user();

            $social = UserSocialMedia::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => $request->type
                ],
                [
                    'url' => $request->url,
                    'is_approved' => 0,
                    'approved_by_admin_id' => null,
                    'approved_on' => null
                ]
            );

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Social media updated successfully',
                'message_code' => 'social_updated',
                'data' => [
                    'user_profile' => getUserProfileDetails($user->id),
                    'type' => $social->type,
                    'url' => $social->url,
                    'is_approved' => $social->is_approved
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
    public function updateProfile(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
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

            $user = auth()->user();

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->save();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Profile updated successfully',
                'message_code' => 'profile_updated',
                'data' => [
                    'user_profile' => getUserProfileDetails($user->id),
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name
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

    public function updatePassword(Request $request)
    {
        try
        {
            $user_id = auth()->id();
            $user = User::find($user_id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => 404,
                    'message' => 'User not found',
                    'message_code' => 'user_not_found',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&]).+$/'
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

            $user = User::find($user_id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => 404,
                    'message' => 'User not found',
                    'message_code' => 'user_not_found',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            // Check current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'Current password is incorrect',
                    'message_code' => 'current_password_not_match',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            // Prevent same password reuse
            if ($request->current_password === $request->password) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'New password must be different from current password',
                    'message_code' => 'same_password_error',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            // Update password
            $user->password = bcrypt($request->password);
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Password updated successfully',
                'message_code' => 'update_success',
                'data' => getUserProfileDetails($user->id),
                'errors' => [],
                'exception' => ''
            ], 200);

        }
        catch (\Exception $exception)
        {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Oops! Something went wrong',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $exception->getMessage()
            ], 200);
        }
    }

   
}
