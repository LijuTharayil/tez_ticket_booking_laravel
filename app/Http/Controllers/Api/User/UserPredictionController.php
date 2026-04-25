<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Prediction;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserPredictionController extends Controller
{
    public function myQuestions(Request $request)
    {
        try {
            $user = auth()->user();

            $today = now()->toDateString();

            $questions = Question::whereDate('question_date', $today)
                ->get();

            $data = $questions->map(function ($q) use ($user) {

                $prediction = Prediction::where('user_id', $user->id)
                    ->where('question_id', $q->id)
                    ->first();

                return [
                    'question_id' => $q->id,
                    'question' => $q->question,
                    'options' => [
                        'A' => $q->option_a,
                        'B' => $q->option_b,
                        'C' => $q->option_c,
                        'D' => $q->option_d,
                    ],
                    'selected_answer' => $prediction->selected_answer ?? null,
                    'is_answered' => $prediction ? true : false,
                ];
            });

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Questions fetched successfully',
                'message_code' => 'questions_fetch_success',
                'data' => $data,
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

    public function submitAnswer(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'question_id' => 'required|exists:questions,id',
                'selected_answer' => 'required|in:A,B,C,D',
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

            $question = Question::find($request->question_id);

            // ❌ Only today's question
            if ($question->question_date != now()->toDateString()) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'Invalid question date',
                    'message_code' => 'invalid_question',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            // ❌ Already answered
            $exists = Prediction::where('user_id', $user->id)
                ->where('question_id', $question->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'Already answered',
                    'message_code' => 'already_answered',
                    'data' => [],
                    'errors' => [],
                    'exception' => ''
                ], 200);
            }

            $isCorrect = null;

            if ($question->correct_answer) {
                $isCorrect = $question->correct_answer == $request->selected_answer;
            }

            $prediction = Prediction::create([
                'user_id' => $user->id,
                'question_id' => $question->id,
                'selected_answer' => $request->selected_answer,
                'is_correct' => $isCorrect
            ]);

            // 🎁 Reward ONLY if correct answer exists AND correct
            if ($isCorrect === true) {
                applyBonus($user, 'PredictBonus');
                createTransaction($user, 'PredictBonus', 'Credit', 10000, $prediction->id, 'Prediction');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Answer submitted successfully',
                'message_code' => 'answer_success',
                'data' => [
                    'is_correct' => $isCorrect
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

    public function myPredictions(Request $request)
    {
        try {
            $user = auth()->user();

            $predictions = Prediction::with('question')
                ->where('user_id', $user->id)
                ->latest()
                ->paginate($request->per_page ?? 10);

            $data = $predictions->map(function ($p) {
                return [
                    'prediction_id' => $p->id,
                    'question' => $p->question->question ?? '',
                    'selected_answer' => $p->selected_answer,
                    'correct_answer' => $p->question->correct_answer,
                    'options' => [
                        'A' => $p->question->option_a,
                        'B' => $p->question->option_b,
                        'C' => $p->question->option_c,
                        'D' => $p->question->option_d,
                    ],
                    'is_correct' => $p->is_correct,
                    'question_date' => $p->question->question_date ?? '',
                ];
            });

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Predictions fetched successfully',
                'message_code' => 'prediction_fetch_success',
                'data' => $data,
                'meta' => getMetaData($predictions),
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
}
