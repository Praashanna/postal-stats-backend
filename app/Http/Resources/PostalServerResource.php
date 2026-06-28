<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostalServerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'permalink' => $this->permalink,
            'mode' => $this->mode,
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'is_active' => $this->is_active,
            'database' => $this->database,
            'status' => $this->is_active ? 'active' : 'inactive',
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization?->id,
                'uuid' => $this->organization?->uuid,
                'name' => $this->organization?->name,
                'permalink' => $this->organization?->permalink,
            ]),
        ];
    }
}
