<?php
namespace App\Http\Controllers;

use App\Http\Resources\SurveyResource;
use App\Http\Resources\SurveyAnswerResource;
use App\Http\Resources\SurveyResourceDashboard;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, $slug)
    {
        $user = $request->user();

        $answer = SurveyAnswer::query()
        ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
        ->where('survey_answers.user_id', $user->id)
        ->where('surveys.slug', $slug)
        ->get();

        $totalAnswers = SurveyAnswer::query()
            ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
            ->where('survey_answers.user_id', $user->id)
            ->where('surveys.slug', $slug) 
            ->count();

        return [
            'totalAnswers' => $totalAnswers,
            // 'survey'=> $survey,
            'answer'=> $answer,
        ];
    }
    
    public function show(Survey $survey)
    {
        $exercise = Survey::query()
            ->select('id', 'slug', 'title','grade')
            ->get();

        return [
            'exercise' => $exercise,
        ];
    }    
    
    public function history(Request $request, $slug)
    {
        $user = $request->user();
        $survey = Survey::where('slug', $slug)->with('questions')->first();
        $test = SurveyAnswer::select('survey_answers.id', 'question_length', 'start_date', 'title', 'slug')
            ->with('surveyQuestionAnswers')
            ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
            ->where('survey_answers.user_id', $user->id)
            ->where('surveys.slug', $slug)
            ->get();
            
            foreach ($test as $surveyAnswer) {
                // Loop through each surveyQuestionAnswer of this SurveyAnswer
                foreach ($surveyAnswer->surveyQuestionAnswers as $questionAnswer) {
                    // Update answer format if it's in the old format
                    $answer = json_decode($questionAnswer->answer, true);
                    if (is_array($answer)) {
                        $questionAnswer->answer = reset($answer); // Get the first element of the array
                    }
                }
            }
        return [
            'data' => SurveyResource::make($survey, false),
            'answers' => $test,
        ];
    }

}