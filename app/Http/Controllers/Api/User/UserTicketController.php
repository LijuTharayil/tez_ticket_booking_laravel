<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\UserTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserTicketController extends Controller
{
    public function purchaseTicket(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'ticket_id' => 'required|exists:tickets,id'
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

            $now = now();

            if ($now->gte(Carbon::parse($ticket->match_time)->subHours(2))) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'Ticket purchase closed (less than 2 hours remaining)',
                    'message_code' => 'purchase_closed',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            if (now()->gte($ticket->match_time)) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'Match already started or completed',
                    'message_code' => 'match_expired',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            // Check balance
            if ($user->total_token_balance < $ticket->ticket_rate_in_coin_quantity) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'Insufficient balance',
                    'message_code' => 'insufficient_balance',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            // Check seat availability
            $bookedCount = UserTicket::where('ticket_id', $ticket->id)->count();
            if ($bookedCount >= $ticket->quantity) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'Tickets sold out',
                    'message_code' => 'sold_out',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            $bookingReference = generateBookingReference();
            // Create user ticket
            $userTicket = UserTicket::create([
                'user_id' => $user->id,
                'ticket_id' => $ticket->id,
                'booked_on' => now(),
                'booking_reference' => $bookingReference,
            ]);

            // Create transaction (Debit)
            createTransaction(
                $user,
                'TicketPurchase',
                'Debit',
                $ticket->ticket_rate_in_coin_quantity,
                $userTicket->id,
                'UserTicket'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Ticket purchased successfully',
                'message_code' => 'ticket_purchase_success',
                'data' => [
                    'ticket_id' => $ticket->id,
                    'ticket_code' => $ticket->ticket_code,
                    'remaining_balance' => $user->fresh()->total_token_balance
                ],
                'errors' => [],
                'exception' => ''
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Oops! Something went wrong',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $e->getMessage()
            ], 200);
        }
    }

    public function getMyTickets(Request $request)
    {
        try {
            $user = auth()->user();

            $perPage = $request->per_page ?? 10;

            $tickets = UserTicket::with('ticket')
                ->where('user_id', $user->id)
                ->latest()
                ->paginate($perPage);

            $data = collect($tickets->items())->map(function ($item) {
                return [
                    'user_ticket_id' => $item->id,
                    'booked_on' => $item->booked_on,

                    'ticket' => [
                        'id' => $item->ticket->id ?? null,
                        'ticket_code' => $item->ticket->ticket_code ?? '',
                        'name' => $item->ticket->name ?? '',
                        'match_title' => $item->ticket->match_title ?? '',
                        'match_details' => $item->ticket->match_details ?? '',
                        'match_time' => $item->ticket->match_time ?? '',
                        'image' => $item->ticket->image ?? '',
                        'ticket_rate' => (string)($item->ticket->ticket_rate_in_coin_quantity ?? 0),
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Tickets fetched successfully',
                'message_code' => 'tickets_fetch_success',
                'data' => $data,
                'meta' => getMetadata($tickets),
                'errors' => [],
                'exception' => ''
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Oops! Something went wrong',
                'message_code' => 'try_catch',
                'data' => [],
                'errors' => [],
                'exception' => $e->getMessage()
            ], 200);
        }
    }

    public function getTickets(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;

            $query = Ticket::query();

            // ✅ Only upcoming matches
            $query->where('match_time', '>', now());

            // ✅ Optional filters
            if ($request->filled('search')) {
                $query->where('match_title', 'LIKE', '%' . $request->search . '%');
            }

            if ($request->filled('date')) {
                $query->whereDate('match_time', $request->date);
            }

            if ($request->filled('venue')) {
                $query->where('venue', $request->venue);
            }

            // ✅ Sorting
            $query->orderBy('match_time', 'asc');

            $tickets = $query->paginate($perPage);

            $data = collect($tickets->items())->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_code' => $ticket->ticket_code,
                    'name' => $ticket->name,
                    'match_title' => $ticket->match_title,
                    'match_details' => $ticket->match_details,
                    'match_time' => $ticket->match_time,
                    'venue' => $ticket->venue,
                    'stadium' => $ticket->stadium,
                    'image' => $ticket->image,
                    'ticket_rate' => (string)$ticket->ticket_rate_in_coin_quantity,

                    // optional
                    'available_quantity' => $ticket->quantity - UserTicket::where('ticket_id', $ticket->id)->count(),
                ];
            });

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Upcoming tickets fetched successfully',
                'message_code' => 'tickets_fetch_success',
                'data' => $data,
                'meta' => getMetaData($tickets),
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
                'meta' => [],
                'errors' => [],
                'exception' => $e->getMessage()
            ], 200);
        }
    }
}
