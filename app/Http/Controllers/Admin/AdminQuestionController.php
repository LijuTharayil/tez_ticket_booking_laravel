<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminQuestionController extends Controller
{
    public function addQuestion(Request $request)
    {
        try {
    
            $validator = Validator::make($request->all(), [
                'question' => 'required|string|max:255',
                'option_a' => 'required|string|max:255',
                'option_b' => 'required|string|max:255',
                'option_c' => 'required|string|max:255',
                'option_d' => 'required|string|max:255',
                'correct_answer' => 'required|in:A,B,C,D',
                'question_date' => 'required|date',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->messages()->first()
                ], 400);
            }
    
            $question = Question::create([
                'question' => $request->question,
                'option_a' => $request->option_a,
                'option_b' => $request->option_b,
                'option_c' => $request->option_c,
                'option_d' => $request->option_d,
                'correct_answer' => strtoupper($request->correct_answer),
                'question_date' => $request->question_date,
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Question added successfully',
                'data' => $question
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'exception' => $e->getMessage()
            ], 500);
        }
    }

    public function editQuestion(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'question_id' => 'required|exists:questions,id',
                'question' => 'sometimes|string|max:255',
                'option_a' => 'sometimes|string|max:255',
                'option_b' => 'sometimes|string|max:255',
                'option_c' => 'sometimes|string|max:255',
                'option_d' => 'sometimes|string|max:255',
                'correct_answer' => 'sometimes|in:A,B,C,D',
                'question_date' => 'sometimes|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->messages()->first()
                ], 400);
            }

            $question = Question::find($request->question_id);

            $question->update([
                'question' => $request->question ?? $question->question,
                'option_a' => $request->option_a ?? $question->option_a,
                'option_b' => $request->option_b ?? $question->option_b,
                'option_c' => $request->option_c ?? $question->option_c,
                'option_d' => $request->option_d ?? $question->option_d,
                'correct_answer' => $request->correct_answer 
                    ? strtoupper($request->correct_answer) 
                    : $question->correct_answer,
                'question_date' => $request->question_date ?? $question->question_date,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'data' => $question
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'exception' => $e->getMessage()
            ], 500);
        }
    }

    public function listQuestions(Request $request)
    {
        $query = Question::query();

        if ($request->filled('question_date')) {
            $query->whereDate('question_date', $request->question_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where('question', 'like', "%$search%");
        }

        $query->orderBy('id', 'desc');

        $questions = $query->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'message' => 'Questions fetched successfully',
            'data' => $questions->items(),
            'meta' => getMetaData($questions),
        ]);
    }

    public function deleteQuestion(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'question_id' => 'required|exists:questions,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->messages()->first()
                ], 400);
            }

            $question = Question::find($request->question_id);
            $question->delete();

            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'exception' => $e->getMessage()
            ], 500);
        }
    }
}
