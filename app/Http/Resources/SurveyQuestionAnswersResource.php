<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SurveyQuestionAnswersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [       
            'id' => $this->id,  
            'survey_question_id' => $this->survey_question_id,
            'survey_answer_id' => $this->survey_answer_id,
            'answer' => json_decode($this->answer),
        ];
    }
}