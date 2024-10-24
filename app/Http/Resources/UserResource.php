<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserResource extends AbstractKnowiiJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'cuid' => $this->cuid,
            'name' => $this->name,
            'username' => $this->username,
            // TODO add user profile (profile property of the user); should use the UserProfile resource
        ];
    }
}
