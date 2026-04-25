<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Transaction;

class UserTransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Transaction::query()
            ->where('user_id', $user->id);

        // 🔍 Filters
        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type); // Credit / Debit
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type); 
            // e.g., TicketPurchase, Deposit, Withdrawal
        }

        if ($request->filled('from_date')) {
            $query->whereDate('transaction_on', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('transaction_on', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('reference_id', 'like', "%$search%")
                ->orWhere('id', $search);
            });
        }

        // 📊 Sorting
        $query->orderBy('id', 'desc');

        // 📄 Pagination
        $transactions = $query->paginate($request->per_page ?? 10);

        $summary = [
            'wallet_balance' => (float) $user->total_token_balance,
            'total_credit' => (float) $user->total_token_credit,
            'total_debit' => (float) $user->total_token_debit,
        ];
        
        return response()->json([
            'status' => true,
            'message' => 'Transactions fetched successfully',
            'data' => $transactions->items(),
            'meta' => getMetaData($transactions),
            'summary' => $summary
        ]);
    }
}
