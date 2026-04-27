<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Prediction;
use App\Models\Question;
use App\Models\Stock;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserBoutique;
use App\Models\UserTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function getAdminDashboard(Request $request)
    {
        try {

            // 👥 Users
            $totalUsers = User::count();
            $verifiedUsers = User::where('is_email_verified', 1)
                ->where('is_mobile_verified', 1)
                ->count();

            // 🎟 Tickets
            $totalTickets = Ticket::count();
            $totalBookings = UserTicket::count();

            // 💰 Wallet Summary (optimized from users table)
            $totalCredit = User::sum('total_token_credit');
            $totalDebit = User::sum('total_token_debit');
            $totalBalance = User::sum('total_token_balance');

            // 🎯 Predictions
            $totalQuestions = Question::count();
            $totalAnswers = Prediction::count();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Dashboard data fetched',
                'message_code' => 'dashboard_success',
                'data' => [

                    'users' => [
                        'total_users' => $totalUsers,
                        'verified_users' => $verifiedUsers,
                    ],

                    'tickets' => [
                        'total_matches' => $totalTickets,
                        'total_bookings' => $totalBookings,
                    ],

                    'wallet' => [
                        'total_given_to_users' => (string)$totalCredit,
                        'total_spent_by_users' => (string)$totalDebit,
                        'total_hold_by_users' => (string)$totalBalance,
                    ],

                    'predictions' => [
                        'total_questions' => $totalQuestions,
                        'total_answers' => $totalAnswers,
                    ]

                ],
                'errors' => [],
                'exception' => ''
            ], 200);

        } catch (\Exception $exception) {

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
}
