<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSurveyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $survey = $this->route('survey');


        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'title' => 'required|string|max:1000',
            'grade' => 'string',
            'video' => 'string',
            'image' => 'string',
            'description' => 'nullable|string',
            'questions' => 'array',
            'question_length' => 'required|integer'
        ];
    }
}