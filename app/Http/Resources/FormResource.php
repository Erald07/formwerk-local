<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FormResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public static function getFormShortLink($shortLink)
  {
    return route('form-from-short-url', $shortLink);
  }
  public function toArray($request)
  {
    $course_ids = $request->get('course_id');
    $ids = [];
    if (!empty($course_ids)) {
      $ids = explode(',', $course_ids);
    }
    $options = json_decode($this->options, true);

    return [
      'id' => $this->id,
      'name' => $this->name,
      'deleted' => $this->deleted,
      'active' => $this->active,
      'userAnonymously' => isset($options['track-count-anonymously']) && $options['track-count-anonymously'] === 'off' ? 0 : 1,
      'link' => FormResource::getFormShortLink($this->short_link),
      'createdAt' => (string) $this->created_at,
      'records' => $this->totalRecords($ids)
    ];
  }
}
