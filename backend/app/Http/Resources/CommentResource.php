<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'body' => $this->body,
            'ticket_id' => $this->ticket_id,
            'user_id' => $this->user_id,
            'organization_id' => $this->organization_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Only include is_internal when viewer is not a customer
        $user = $request->user();
        if ($user && ! $user->isCustomer()) {
            $data['is_internal'] = $this->is_internal;
        }

        return $data;
    }
}
