<?php

namespace App\Tests\Mock;

use App\DTO\UserDto;
use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Component\Serializer\SerializerInterface;

class BillingClientMock extends BillingClient
{
    private array $user;

    private array $admin;

    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct($serializer);
        $this->user = [
            'username' => 'my_user@email.com',
            'password' => 'user',
            'balance' => 1000,
            'roles' => ['ROLE_USER']
        ];
        $this->admin = [
            'username' => 'my_admin@email.com',
            'password' => 'admin',
            'balance' => 1000,
            'roles' => ['ROLE_SUPER_ADMIN', 'ROLE_USER']
        ];
        $this->serializer = $serializer;
    }

    /**
     * @throws BillingException
     */
    public function login($credentials): User
    {
        $credentials = json_decode($credentials, true);
        $username = $credentials['username'];
        $password = $credentials['password'];
        $user = new User();
        if ($username == $this->user['username'] && $password == $this->user['password']) {
            $token = $this->generateToken($username, $this->user['roles']);
            $user
                ->setEmail($username)
                ->setApiToken($token)
                ->setRoles($this->user['roles']);
            return $user;
        } elseif ($username == $this->admin['username'] && $password == $this->admin['password']) {
            $token = $this->generateToken($username, $this->admin['roles']);
            $user
                ->setEmail($username)
                ->setApiToken($token)
                ->setRoles($this->admin['roles']);
            return $user;
        } else {
            throw new BillingException('Ошибка авторизации.');
        }
    }

    /**
     * @throws BillingException
     */
    public function register($credentials): User
    {
        $credentials = json_decode($credentials, true);
        $username = $credentials["username"];
        if ($username === $this->user['username'] || $username === $this->admin['username']) {
            throw new BillingException(json_encode('Пользователь с такой почтой уже существует', true));
        }
        $token = $this->generateToken($username, $this->user['roles']);
        $user = new User();
        $user
            ->setEmail($username)
            ->setApiToken($token)
            ->setRoles($this->admin['roles']);
        return $user;
    }

    public function currentUser($token): UserDto
    {
        $user = $this->decodeToken($token);
        $userDto = new UserDto();
        $userDto->setUsername($user['username']);
        $userDto->setRoles($user['roles']);
        if ($user['username'] === $this->user['username']) {
            $userDto->setBalance($this->user['balance']);
        } elseif ($user['username'] === $this->admin['username']) {
            $userDto->setBalance($this->admin['balance']);
        }
        return $userDto;
    }

    private function generateToken(string $username, array $roles): string
    {
        $data = [
            'username' => $username,
            'roles' => $roles
        ];
        $query = base64_encode(json_encode($data));
        return 'header.' . $query . '.signature';
    }

    private function decodeToken($token): array
    {
        $parts = explode('.', $token);
        $payload = json_decode(base64_decode($parts[1]), true);
        return ['username' => $payload['username'], 'roles' => $payload['roles']];
    }
}
