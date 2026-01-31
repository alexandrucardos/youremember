<?php

namespace App\Security;

use App\Entity\Profile;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @deprecated
 */
class ProfileVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof Profile;
    }

    protected function voteOnAttribute(string $attribute, $profile, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if ($user === null) {
            return false;
        }

        return $profile->getUser() === $user;
    }
}

