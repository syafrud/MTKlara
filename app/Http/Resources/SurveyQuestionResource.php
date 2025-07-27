<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;


class SurveyQuestionResource extends JsonResource
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


        $data = json_decode($this->data);
        foreach ($data->options as &$option) 
            if ($this->pertanyaan)
                unset($option->correct);
            
        return [
            'id' => $this->id,
            'type' => $this->type,
            'question' => $this->question,
            'description' => $this->description,
            'data' =>  $data,
        ];
    }
}