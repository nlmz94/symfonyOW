<?php

namespace App\Serializer;

use App\Entity\User;

final readonly class UserSerializer
{
    private const string DEFAULT_PROFILE_PIC = '/images/defaultProfileImage.png';

    public function __construct(private ImageUrlGenerator $images)
    {
    }

    /**
     * The signed-in user's own account: login, register, GET /api/me, profile picture upload.
     *
     * @return array<string, mixed>
     */
    public function accountSerialize(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()?->format(\DATE_ATOM),
            'profilePic' => $this->images->filtered($user->getProfilePic() ?: self::DEFAULT_PROFILE_PIC, 'profile'),
        ];
    }
}
