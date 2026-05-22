<?php

namespace App\Policies;

use App\Models\Upload;
use App\Models\User;

class UploadPolicy
{
    public function update(User $user, Upload $upload): bool
    {
        return $user->id === $upload->user_id;
    }
}
