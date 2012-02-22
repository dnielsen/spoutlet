<?php

namespace Platformd\UserBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Platformd\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * API for users
 */
class ApiController extends Controller
{
    /**
     * Returns a bunch of users' details all at once
     *
     * Requires a 'users' POST parameter that is a CSV of usernames
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function usersDetailsAction(Request $request)
    {
        $usersStr = $request->request->get('users');
        $users = explode(',', $usersStr);

        $data = array();
        foreach ($users as $user) {
            $userObj = $this->getUserManager()->findUserBy(array('id' => trim($user)));

            if (!$userObj) {
                continue;
            }

            $data[$userObj->getId()] = $this->userToArray($userObj);
        }

        return new Response(json_encode($data));
    }

    /**
     * Returns details for *just* the authenticated user
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function authenticatedUserDetailsAction(Request $request)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $data = array(
                'error' => true,
                'message' => 'No user is currently authenticated',
            );

            return new Response(json_encode($data));
        }

        $userData = $this->userToArray($this->getUser());

        return new Response(json_encode(array(
            'user' => $userData
        )));
    }

    /**
     * Very cheap, and probably temporary method to serialize users to an array
     *
     * @param \Platformd\UserBundle\Entity\User $user
     * @return array
     */
    private function userToArray(User $user)
    {
        $exposer = $this->container->get('media_exposer');
        $path = $user->getApprovedAvatar() ? $exposer->getPath($user) : '/images/profile-default.png';
        $avatarUrl = $this->container->get('templating.helper.assets')->getUrl($path);

        $profileUrl = $this->generateUrl('accounts_profile', array(
            'username' => $user->getUsername(),
        ));

        //asset(user.avatar and user.isAvatarApproved ? media_path(user) : "/images/profile-default.png");

        return array(
            'id'       => $user->getId(),
            'username' => $user->getUsername(),
            'handle'   => $user->getUsername(),
            'country'  => $user->getCountry(),
            'avatar_url' => $avatarUrl,
            'profile_url' => $profileUrl,
        );
    }
}
