<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class SurveyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

     protected $pertanyaan;

    public function __construct($resource, $pertanyaan)
    {
        parent::__construct($resource);
        $this->pertanyaan = $pertanyaan;

    }


    public function toArray($request)
    {
        $pertanyaan=$this->pertanyaan;
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'grade' => $this->grade,
            'video_url' => $this->video ? URL::to($this->video) : null,
            'image_url' => $this->image ? URL::to($this->image) : null,
            'description' => $this->description,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'questions' =>  $this->questions->map(function ($question) use ($pertanyaan) {
                return new SurveyQuestionResource($question, $pertanyaan);
            }),
            'questions_length' => $this->question_length,
        ];
    }
}