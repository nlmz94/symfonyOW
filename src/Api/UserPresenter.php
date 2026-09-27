<?php

namespace App\Api;

use App\Entity\User;

final readonly class UserPresenter
{
    private const string DEFAULT_PROFILE_PIC = '/images/defaultProfileImage.png';

    public function __construct(private ImageUrlGenerator $images)
    {
    }

    /** @return array<string, mixed> */
    public function present(User $user): array
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
