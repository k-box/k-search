<?php

namespace App\Security\Authenticator;

use App\Model\Error\Error;
use App\Model\Error\ErrorResponse;
use App\Security\Provider\KLinkRegistryUserProvider;
use Symfony\Component\HttpFoundation\HeaderBag;
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
    private const TOKEN_MIN_LENGTH = 5;

    /**
     * Called on every request. Return the credentials needed or null to stop authentication.
     *
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        $appSecret = $this->getAppSecretFromHeaders($request->headers);
        if (null === $appSecret) {
            // Missing Authorization header or wrong format: return null and no other methods will be called
            return null;
        }

        $appUrl = $request->headers->get('Origin');
        if (!$appUrl) {
            // Missing app-url: return null and no other methods will be called
            return null;
        }

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
        $errorResponse = new ErrorResponse(new Error(
            Response::HTTP_UNAUTHORIZED,
            'Wrong API Authentication provided',
            $exception->getMessageKey()
        ));

        return new JsonResponse($errorResponse);
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

    /**
     * Returns the app-secret form the request headers.
     *
     * @param HeaderBag $headers
     *
     * @return string|null The app-secret as a string, null if not valid or not found
     */
    private function getAppSecretFromHeaders(HeaderBag $headers): ?string
    {
        $authorizationHeader = $headers->get('Authorization');

        if (!is_string($authorizationHeader)) {
            return null;
        }

        // Remove any extra spaces
        $authorizationHeader = trim($authorizationHeader);
        if (!$authorizationHeader || 0 !== strpos($authorizationHeader, 'Token ')) {
            return null;
        }

        if (strlen($authorizationHeader) < (6 + self::TOKEN_MIN_LENGTH)) {
            return null;
        }

        // Extract the app-secret from "Authorization: Token A1B2C3...."
        return substr($authorizationHeader, 6);
    }
}
