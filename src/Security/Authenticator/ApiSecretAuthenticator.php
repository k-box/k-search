<?php

namespace App\Security\Authenticator;

use App\Model\Error\Error;
use App\Model\Error\ErrorResponse;
use App\Security\Provider\KLinkRegistryUserProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiSecretAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * Called on every request. Return the credentials needed or null to stop authentication.
     *
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        $authorizationHeader = $request->headers->get('Authorization');
        if (!$authorizationHeader || 0 !== strpos($authorizationHeader, 'Token ')) {
            // Missing Authorization headers, return null and no other methods will be called
            return null;
        }

        $appUrl = $request->headers->get('Origin');
        if (!$appUrl) {
            // Missing app-url: return null and no other methods will be called
            return null;
        }

        // Extract the app-secret from "Authorization: Token A1B2C3...."
        $appSecret = substr($authorizationHeader, 6);

        return [
            'app_url' => $appUrl,
            'app_secret' => $appSecret,
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (!$userProvider instanceof KLinkRegistryUserProvider) {
            throw new \RuntimeException(sprintf('Authenticator %s is expecting %s provider, while %s has been used',
                __CLASS__,
                    get_class($userProvider),
                KLinkRegistryUserProvider::class
            ));
        }

        return $userProvider->loadUserFromApplicationUrlAndSecret(
            $credentials['app_url'],
            $credentials['app_secret']
        );
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $user->getPassword() === $credentials['app_secret'];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Called when authentication is needed, but it's not sent.
     *
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $errorResponse = new ErrorResponse(new Error(
            Response::HTTP_UNAUTHORIZED,
            'API Authentication Required',
            $authException ? $authException->getMessageKey() : null
        ));

        return new JsonResponse($errorResponse);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
