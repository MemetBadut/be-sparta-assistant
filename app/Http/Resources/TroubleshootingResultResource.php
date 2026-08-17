<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TroubleshootingResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->result_payload + ['id' => $this->id];
    }
}
