<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FrontMovie extends JsonResource
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
            'title' => $this->title,
            'episode' => $this->episode,
            'type' => $this->type,
            'year' => $this->year,
            'thumbnail' => $this->thumbnail,
            'trailer' => $this->trailer,
            'video' => $this->video,
            'video_id' => $this->video_id,
            'description' => $this->description,
        ];
    }
}
