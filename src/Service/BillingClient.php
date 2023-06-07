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
        return User::fromDto($this->currentUser($result['token']), $result['token'], $result['refresh_token']);
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
        return User::fromDto($this->currentUser($result['token']), $result['token'], $result['refresh_token']);
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

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     */
    public function refresh($refresh_token): User
    {
        $response = (new ApiClient())->post('/api/v1/token/refresh', $refresh_token);
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            if ($result['code'] === 401) {
                throw new BillingException('Ошибка авторизации.');
            } elseif ($result['code'] !== 200) {
                throw new BillingException($result['message']);
            }
        }
        return User::fromDto($this->currentUser($result['token']), $result['token'], $result['refresh_token']);
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     */
    public function getCourses(): array
    {
        $response = (new ApiClient())->get('/api/v1/courses');
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            throw new BillingException($result['message']);
        }
        return $result;
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     */
    public function getCourse(string $code)
    {
        $code = preg_replace("/ /", '%20', $code);
        $response = (new ApiClient())->get("/api/v1/courses/$code");
        $result = json_decode($response, true);
        if (isset($result['message'])) {
            throw new BillingException($result['message']);
        }
        return $result;
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     */
    public function payCourse(string $code, string $userToken)
    {
        $response = (new ApiClient())->post("/api/v1/courses/$code/pay", null, [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $userToken,
        ]);
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            throw new BillingException($result['message']);
        }
         return $result;
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     */
    public function getTransactions(string $userToken, array $filters): array
    {
        $response = (new ApiClient())->get('/api/v1/transactions?' . http_build_query($filters), [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $userToken,
        ]);
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            throw new BillingException($result['message']);
        }
        return $result;
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     */
    public function addCourse(string $userToken, $data): array
    {
        $response = (new ApiClient())->post('/api/v1/courses', $data, [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $userToken,
        ]);
        $result = json_decode($response, true);
        if (!$result['success']) {
            throw new BillingException($result['message']);
        }
        return $result;
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     */
    public function editCourse(string $userToken, string $code, $data): array
    {
        $code = preg_replace("/ /", '%20', $code);
        $response = (new ApiClient())->post("/api/v1/courses/$code", $data, [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $userToken,
        ]);
        $result = json_decode($response, true);
        if (!$result['success']) {
            throw new BillingException($result['message']);
        }
        return $result;
    }
}
