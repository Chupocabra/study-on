<?php

namespace App\Service;

use App\DTO\UserDto;
use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use Symfony\Component\Serializer\SerializerInterface;

class BillingClient
{
    protected SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     */
    public function login($credentials): User
    {
        $response = (new ApiClient())->post('/api/v1/auth', $credentials);
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            if ($result['code'] === 401) {
                throw new BillingException('Ошибка авторизации, проверьте введенные данные.');
            } else {
                throw new BillingException($result['message']);
            }
        }
        return User::fromDto($this->currentUser($result['token']))->setApiToken($result['token']);
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     */
    public function register($credentials): User
    {
        $response = (new ApiClient())->post('/api/v1/register', $credentials);
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            if ($result['code'] === 400) {
                throw new BillingException(json_encode($result['errors'], true));
            } else {
                throw new BillingException(json_encode($result['message'], true));
            }
        }
        return User::fromDto($this->currentUser($result['token']))->setApiToken($result['token']);
    }

    /**
     * @throws BillingException
     * @throws BillingUnavailableException
     */
    public function currentUser($token)
    {
        $response = (new ApiClient())->get('/api/v1/users/current', ['Authorization: Bearer ' . $token]);
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            throw new BillingException($result['message']);
        }
        return $this->serializer->deserialize($response, UserDto::class, 'json');
    }
}
