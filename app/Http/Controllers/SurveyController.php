<?php

namespace App\Http\Controllers;

use App\Enums\QuestionTypeEnum;
use App\Http\Requests\StoreSurveyAnswerRequest;
use App\Http\Resources\SurveyResource;
use App\Http\Resources\SurveyAnswerResource;
use App\Http\Resources\SurveyQuestionAnswersResource;
// use App\Http\Resources\SurveyQuestionAnswerResource;
use App\Models\Survey;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionAnswers;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpFoundation\Request;

class SurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $surveys = Survey::query()
            ->orderByRaw('CAST(grade AS UNSIGNED) ASC')
            ->orderBy('title', 'asc')
            ->paginate(6);

        return SurveyResource::collection($surveys);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreSurveyRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSurveyRequest $request)
    {
        $data = $request->validated();

        // Check if video was given and save on local file system
        if (isset($data['video'])) {
            $relativePath = $this->saveVideo($data['video']);
            $data['video'] = $relativePath;
        }

        if (isset($data['image'])) {
            $relativePathi = $this->saveImage($data['image']);
            $data['image'] = $relativePathi;
        }

        $survey = Survey::create($data);

        // Create new questions
        foreach ($data['questions'] as $question) {
            $question['survey_id'] = $survey->id;
            $this->createQuestion($question);
        }

        return new SurveyResource($survey, false);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Survey  $survey
     * @return \Illuminate\Http\Response
     */
    public function show(Survey $survey, Request $request)
    {
        return new SurveyResource($survey, false);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSurveyRequest  $request
     * @param  \App\Models\Survey  $survey
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSurveyRequest $request, Survey $survey)
    {
        $data = $request->validated();

        // Check if video was given and save on local file system
        if (isset($data['video'])) {
            $relativePath = $this->saveVideo($data['video']);
            $data['video'] = $relativePath;

            // If there is an old video, delete it
            if ($survey->video) {
                $absolutePath = public_path($survey->video);
                File::delete($absolutePath);
            }
        }

        if (isset($data['image'])) {
            $relativePathi = $this->saveImage($data['image']);
            $data['image'] = $relativePathi;

            // If there is an old image, delete it
            if ($survey->image) {
                $absolutePath = public_path($survey->image);
                File::delete($absolutePath);
            }
        }

        // Update survey in the database
        $survey->update($data);

        // Get ids as plain array of existing questions
        $existingIds = $survey->questions()->pluck('id')->toArray();
        // Get ids as plain array of new questions
        $newIds = Arr::pluck($data['questions'], 'id');
        // Find questions to delete
        $toDelete = array_diff($existingIds, $newIds);
        //Find questions to add
        $toAdd = array_diff($newIds, $existingIds);

        // Delete questions by $toDelete array
        SurveyQuestion::destroy($toDelete);

        // Create new questions
        foreach ($data['questions'] as $question) {
            if (in_array($question['id'], $toAdd)) {
                $question['survey_id'] = $survey->id;
                $this->createQuestion($question);
            }
        }

        // Update existing questions
        $questionMap = collect($data['questions'])->keyBy('id');
        foreach ($survey->questions as $question) {
            if (isset($questionMap[$question->id])) {
                $this->updateQuestion($question, $questionMap[$question->id]);
            }
        }

        return new SurveyResource($survey, false);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Survey  $survey
     * @return \Illuminate\Http\Response
     */
    public function destroy(Survey $survey, Request $request)
    {


        $survey->delete();

        // If there is an old video, delete it
        if ($survey->video) {
            $absolutePath = public_path($survey->video);
            File::delete($absolutePath);
        }

        return response('', 204);
    }


    /**
     * Save video in local file system and return saved video path
     *
     * @param $video
     * @throws \Exception
     * @author Zura Sekhniashvili <zurasekhniashvili@gmail.com>
     */
    private function saveVideo($video)
    {
        // Check if video is valid base64 string
        if (preg_match('/^data:video\/(\w+);base64,/', $video, $type)) {
            // Take out the base64 encoded text without mime type
            $video = substr($video, strpos($video, ',') + 1);
            // Get file extension
            $type = strtolower($type[1]); // jpg, png, gif

            // Check if file is an video
            if (!in_array($type, ['mp4', 'ogx', 'oga', 'ogv', 'ogg', 'webm'])) {
                throw new \Exception('invalid video type');
            }
            $video = str_replace(' ', '+', $video);
            $video = base64_decode($video);

            if ($video === false) {
                throw new \Exception('base64_decode failed');
            }
        } else {
            throw new \Exception('did not match data URI with video data');
        }

        $dir = 'videos/';
        $file = Str::random() . '.' . $type;
        $absolutePath = public_path($dir);
        $relativePath = $dir . $file;
        if (!File::exists($absolutePath)) {
            File::makeDirectory($absolutePath, 0755, true);
        }
        file_put_contents($relativePath, $video);

        return $relativePath;
    }

    private function saveImage($image)
    {
        // Check if image is valid base64 string
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            // Take out the base64 encoded text without mime type
            $image = substr($image, strpos($image, ',') + 1);
            // Get file extension
            $type = strtolower($type[1]); // jpg, png, gif

            // Check if file is an image
            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                throw new \Exception('invalid image type');
            }
            $image = str_replace(' ', '+', $image);
            $image = base64_decode($image);

            if ($image === false) {
                throw new \Exception('base64_decode failed');
            }
        } else {
            throw new \Exception('did not match data URI with image data');
        }

        $dir = 'images/';
        $file = Str::random() . '.' . $type;
        $absolutePath = public_path($dir);
        $relativePathi = $dir . $file;
        if (!File::exists($absolutePath)) {
            File::makeDirectory($absolutePath, 0755, true);
        }
        file_put_contents($relativePathi, $image);

        return $relativePathi;
    }

    /**
     * Create a question and return
     *
     * @param $data
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     * @author Zura Sekhniashvili <zurasekhniashvili@gmail.com>
     */
    private function createQuestion($data)
    {
        if (is_array($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }
        $validator = Validator::make($data, [
            'question' => 'required|string',
            'type' => [
                'required',
                new Enum(QuestionTypeEnum::class)
            ],
            'description' => 'nullable|string',
            'data' => 'present',
            'survey_id' => 'exists:App\Models\Survey,id'
        ]);

        return SurveyQuestion::create($validator->validated());
    }

    /**
     * Update a question and return true or false
     *
     * @param \App\Models\SurveyQuestion $question
     * @param                            $data
     * @return bool
     * @throws \Illuminate\Validation\ValidationException
     * @author Zura Sekhniashvili <zurasekhniashvili@gmail.com>
     */
    private function updateQuestion(SurveyQuestion $question, $data)
    {
        if (is_array($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }
        $validator = Validator::make($data, [
            'id' => 'exists:App\Models\SurveyQuestion,id',
            'question' => 'required|string',
            'type' => ['required', new Enum(QuestionTypeEnum::class)],
            'description' => 'nullable|string',
            'data' => 'present',
        ]);

        return $question->update($validator->validated());
    }

    public function getByPertanyaan(Survey $survey)
    {
        return new SurveyResource($survey, true);
    }
    public function getByJawaban(Survey $survey)
    {
        return new SurveyResource($survey, false);
    }

    public function getSurveyHistory(Request $request, $id)
    {
        $userId = $request->user()->id;

        $surveyAnswer = SurveyAnswer::with('surveyQuestionAnswers')
            ->where('id', $id)
            ->firstOrFail();

        if ($surveyAnswer->user_id !== $userId) {
            return response()->json([
                'message' => 'Unauthorized access to survey history.'
            ], 403);
        }

        return new SurveyAnswerResource($surveyAnswer);
    }

    public function storeAnswer(StoreSurveyAnswerRequest $request, Survey $survey)
    {
        try {
            $validated = $request->validated();
            $correctAnswers = $validated['answers']['correctAnswers'] ?? 0;
            $userId = $request->user()->id;

            $surveyAnswer = SurveyAnswer::create([
                'user_id' => $userId,
                'survey_id' => $survey->id,
                'start_date' => date('Y-m-d H:i:s'),
                'end_date' => date('Y-m-d H:i:s'),
                'correctAnswers' => $correctAnswers,
            ]);

            foreach ($validated['answers'] as $questionId => $answer) {
                if ($questionId === 'correctAnswers') {
                    continue; // Skip the "correctAnswers" key
                }

                $question = SurveyQuestion::where(['id' => $questionId, 'survey_id' => $survey->id])->first();
                if (!$question) {
                    return response("Invalid question ID: $questionId", 400);
                }

                $data = [
                    'survey_question_id' => $questionId,
                    'survey_answer_id' => $surveyAnswer->id,
                    'answer' => is_array($answer) ? json_encode($answer) : $answer,
                ];
                $questionAnswer = SurveyQuestionAnswers::create($data);
            }

            return response("", 201);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return response("Internal server error", 500);
        }
    }
}
