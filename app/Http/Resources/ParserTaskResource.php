<?php

namespace App\Http\Resources;

use App\Models\ParserType;
use Illuminate\Http\Resources\Json\JsonResource;

class ParserTaskResource extends JsonResource
{
    public static $wrap = null;
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
            'status' => $this->status,
            'type' => ParserType::find($this->type_id)->index,
            'table_name' => $this->table_name,
            'selected_table' => $this->selected_table,
            'logins' => $this->logins,
        ];
    }
}
