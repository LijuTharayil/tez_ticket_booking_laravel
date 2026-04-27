<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\UserTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminTicketController extends Controller
{
    public function getTickets(Request $request)
    {
        try {
    
            $query = Ticket::query();
    
            // 🔍 Search (optional)
            if ($request->search) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('match_title', 'like', '%' . $request->search . '%');
            }
    
            // 📅 Filter by date
            if ($request->date) {
                $query->whereDate('match_time', $request->date);
            }
    
            $tickets = $query->latest()->paginate(10);
    
            // Add purchase count
            $tickets = Ticket::query()
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('match_title', 'like', '%' . $request->search . '%');
            })
            ->latest()
            ->paginate($request->per_page ?? 10);
        
        $data = $tickets->map(function ($ticket) {
            $totalPurchased = UserTicket::where('ticket_id', $ticket->id)->count();
        
            return [
                'id' => $ticket->id,
                'ticket_code' => $ticket->ticket_code,
                'name' => $ticket->name,
                'match_title' => $ticket->match_title,
                'match_details' => $ticket->match_details,
                'quantity' => $ticket->quantity,
                'match_time' => $ticket->match_time,
                'ticket_rate_in_coin_quantity' => (string)$ticket->ticket_rate_in_coin_quantity,
                'total_purchased' => $totalPurchased,
                'remaining_tickets' => $ticket->quantity - $totalPurchased,
            ];
        });
        
        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Tickets fetched successfully',
            'message_code' => 'tickets_fetched',
            'data' => $data,
            'meta_data' => getMetaData($tickets),
            'errors' => [],
            'exception' => ''
        ], 200);
    
        } catch (\Exception $e) {
    
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Something went wrong',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function addTicket(Request $request)
    {
        try {

            $admin = auth('admin')->user();

            $validator = Validator::make($request->all(), [
                'ticket_code' => 'required|unique:tickets,ticket_code',
                'quantity' => 'required|integer|min:1',
                'match_time' => 'required|date',
                'name' => 'required|string|max:255',
                'match_title' => 'required|string|max:255',
                'match_details' => 'nullable|string',
                'venue' => 'nullable|string|max:255',
                'stadium' => 'nullable|string|max:255',
                'ticket_rate_in_coin_quantity' => 'required|numeric|min:0',
                'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
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

            // ✅ Handle Image Upload
            if ($request->hasFile('image')) {

               
                $file = $request->file('image');

                // create filename from ticket name
                $fileName = Str::slug($request->ticket_code).'-'.time(). '.' . $file->getClientOriginalExtension();

                // store in storage/app/public/tickets
                $path = $file->storeAs('tickets', $fileName, 'public');
            }


            $ticket = Ticket::create([
                'ticket_code' => $request->ticket_code,
                'quantity' => $request->quantity,
                'match_time' => $request->match_time,
                'name' => $request->name,
                'match_title' => $request->match_title,
                'match_details' => $request->match_details,
                'venue' => $request->venue,
                'stadium' => $request->stadium,
                'image' => $fileName,
                'ticket_rate_in_coin_quantity' => $request->ticket_rate_in_coin_quantity,
                'added_by_admin_id' => $admin->id,
            ]);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Ticket created successfully',
                'message_code' => 'ticket_created',
                'data' => $ticket,
                'errors' => [],
                'exception' => ''
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Something went wrong',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $e->getMessage()
            ], 200);
        }
    }

    public function editTicket(Request $request)
    {
        try {

            $admin = auth('admin')->user();

            $validator = Validator::make($request->all(), [
                'ticket_id' => 'required|exists:tickets,id',
                'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
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

            $ticket = Ticket::find($request->ticket_id);

            // ✅ Total booked tickets
            $totalBooked = UserTicket::where('ticket_id', $ticket->id)->count();
            $isPurchased = $totalBooked > 0;

            // ❌ Prevent price update
            if ($isPurchased && $request->has('ticket_rate_in_coin_quantity')) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'Cannot update price, ticket already purchased',
                    'message_code' => 'price_locked',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            // ❌ Quantity validation
            if ($request->has('quantity') && $request->quantity < $totalBooked) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => "Quantity cannot be less than booked tickets ($totalBooked)",
                    'message_code' => 'invalid_quantity',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

          // ✅ Handle Image Upload
            if ($request->hasFile('image')) {

                // delete old image
                if ($ticket->image && Storage::disk('public')->exists($ticket->image)) {
                    Storage::disk('public')->delete($ticket->image);
                }

                $file = $request->file('image');

                // create filename from ticket name
                $fileName = Str::slug($request->ticket_code).'-'.time(). '.' . $file->getClientOriginalExtension();

                // store in storage/app/public/tickets
                $path = $file->storeAs('tickets', $fileName, 'public');

                // save path
                $ticket->image = $fileName;
            }

            $ticket->update([
                'quantity' => $request->quantity ?? $ticket->quantity,
                'match_time' => $request->match_time ?? $ticket->match_time,
                'name' => $request->name ?? $ticket->name,
                'match_title' => $request->match_title ?? $ticket->match_title,
                'match_details' => $request->match_details ?? $ticket->match_details,
                'venue' => $request->venue ?? $ticket->venue,
                'stadium' => $request->stadium ?? $ticket->stadium,
                'ticket_rate_in_coin_quantity' => $request->ticket_rate_in_coin_quantity ?? $ticket->ticket_rate_in_coin_quantity,
                'last_updated_by_admin_id' => $admin->id,
            ]);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Ticket updated successfully',
                'message_code' => 'ticket_updated',
                'data' => $ticket,
                'errors' => [],
                'exception' => ''
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Something went wrong',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $e->getMessage()
            ], 200);
        }
    }

    public function deleteTicket(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'ticket_id' => 'required|exists:tickets,id',
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
                ]);
            }

            $ticket = Ticket::find($request->ticket_id);

            // ❌ Prevent delete if purchased
            $isPurchased = UserTicket::where('ticket_id', $ticket->id)->exists();

            if ($isPurchased) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'Cannot delete, ticket already purchased',
                    'message_code' => 'ticket_locked',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ]);
            }

            $ticket->delete();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Ticket deleted successfully',
                'message_code' => 'ticket_deleted',
                'data' => [],
                'errors' => [],
                'exception' => ''
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Something went wrong',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function transactionsList(Request $request)
    {
        $query = Transaction::with('user:id,first_name,last_name,user_unique_id');

        // 🔍 Filters

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
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

        if ($request->filled('user_search')) {
            $search = $request->user_search;
        
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('user_unique_id', 'like', "%$search%");
            });
        }

        // 📊 Sorting
        $query->orderBy('id', 'desc');

        // 📄 Pagination
        $transactions = $query->paginate($request->per_page ?? 10);

        // ✅ Format response (merge user data)
        $data = collect($transactions->items())->map(function ($txn) {
            return [
                'id' => $txn->id,
                'user_id' => $txn->user_id,
                'first_name' => $txn->user->first_name ?? null,
                'last_name' => $txn->user->last_name ?? null,
                'user_unique_id' => $txn->user->user_unique_id ?? null,
                'type' => $txn->type,
                'account_type' => $txn->account_type,
                'amount' => $txn->amount,
                'transaction_on' => $txn->transaction_on,
                'reference_id' => $txn->reference_id,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'All transactions fetched successfully',
            'data' => $data,
            'meta' => getMetaData($transactions),
        ]);
    }
}
