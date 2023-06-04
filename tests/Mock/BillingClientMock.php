<?php

namespace App\Tests\Mock;

use App\DTO\UserDto;
use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use App\Service\JwtDecode;
use Symfony\Component\Serializer\SerializerInterface;

class BillingClientMock extends BillingClient
{
    private array $user;

    private array $admin;

    private array $courses;

    private array $admin_transactions;

    private array $user_transactions;

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
        $this->courses = [
            [
                "code" => "frontend-dev",
                "type" => "free",
                "price" => 0
            ],
            [
                "code" => "python-dev",
                "type" => "rent",
                "price" => 1000
            ],
            [
                "code" => "data-analyst",
                "type" => "rent",
                "price" => 800
            ],
            [
                "code" => "java-dev",
                "type" => "buy",
                "price" => 2800
            ],
            [
                "code" => "php-dev",
                "type" => "buy",
                "price" => 3200
            ],
        ];
        $this->user_transactions = [
            [
                "id" => 15,
                "created_at" => [
                    "date" => "2023-05-11 09:09:20.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ],
                "type" => "payment",
                "course_code" => "python-dev",
                "value" => 1000,
                "expires" => [
                    "date" => "2023-05-18 09:09:20.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ]
            ],
            [
                "id" => 3,
                "created_at" => [
                    "date" => "2023-05-10 16:37:23.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ],
                "type" => "deposit",
                "value" => 1000,
                "expires" => null
            ],
            [
                "id" => 10,
                "created_at" => [
                    "date" => "2023-05-02 16:37:24.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ],
                "type" => "payment",
                "course_code" => "java-dev",
                "value" => 2800,
                "expires" => null
            ],
            [
                "id" => 8,
                "created_at" => [
                    "date" => "2023-04-10 16:37:24.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ],
                "type" => "deposit",
                "value" => 4000,
                "expires" => null
            ],
            [
                "id" => 7,
                "created_at" => [
                    "date" => "2023-04-08 16:37:24.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ],
                "type" => "payment",
                "course_code" => "data-analyst",
                "value" => 800,
                "expires" => [
                    "date" => "2023-04-13 16:37:24.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ]
            ],
            [
                "id" => 6,
                "created_at" => [
                    "date" => "2023-04-08 16:37:24.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ],
                "type" => "deposit",
                "value" => 600,
                "expires" => null
            ],
            [
                "id" => 5,
                "created_at" => [
                    "date" => "2023-03-31 16:37:24.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ],
                "type" => "payment",
                "course_code" => "data-analyst",
                "value" => 800,
                "expires" => [
                    "date" => "2023-04-07 16:37:24.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ]
            ]
        ];
        $this->admin_transactions = [
            [
                "id" => 4,
                "created_at" => [
                    "date" => "2023-05-10 16:37:23.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ],
                "type" => "deposit",
                "value" => 1000,
                "expires" => null
            ],
            [
                "id" => 16,
                "created_at" => [
                    "date" => "2023-05-11 11:45:47.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ],
                "type" => "payment",
                "course_code" => "python-dev",
                "value" => 1000,
                "expires" => [
                    "date" => "2023-05-18 11:45:47.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ]
            ],
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
                ->setRefreshToken('123456qwerty')
                ->setRoles($this->user['roles']);
            return $user;
        } elseif ($username == $this->admin['username'] && $password == $this->admin['password']) {
            $token = $this->generateToken($username, $this->admin['roles']);
            $user
                ->setEmail($username)
                ->setApiToken($token)
                ->setRefreshToken('123456qwerty')
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
            throw new BillingException(json_encode('MOCK:Пользователь с такой почтой уже существует', true));
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
        [$exp, $roles, $username] = (new JwtDecode())->jwtDecode($token);
        $userDto = new UserDto();
        $userDto->setUsername($username);
        $userDto->setRoles($roles);
        if ($username === $this->user['username']) {
            $userDto->setBalance($this->user['balance']);
        } elseif ($username === $this->admin['username']) {
            $userDto->setBalance($this->admin['balance']);
        }
        return $userDto;
    }

    public function getCourses(): array
    {
        return $this->courses;
    }

    public function getCourse(string $code)
    {
        foreach ($this->courses as $course) {
            if ($course['code'] === $code) {
                return $course;
            }
        }
        return 'Курсов не найдено';
    }

    public function getTransactions(string $userToken, array $filters): array
    {
        $username = (new JwtDecode())->jwtDecode($userToken)[2];
        if ($username == $this->user['username']) {
            $transactions = $this->user_transactions;
        } elseif ($username == $this->admin['username']) {
            $transactions = $this->admin_transactions;
        } else {
            throw new BillingException('MOCK:Доступ запрещен');
        }
        if (isset($filters['type'])) {
            $transactions = array_filter($transactions, function ($transaction) use ($filters) {
                return $transaction['type'] === $filters['type'];
            });
        }
        if (isset($filters['course_code'])) {
            $transactions = array_filter($transactions, function ($transaction) use ($filters) {
                return $transaction['course_code'] === $filters['course_code'];
            });
        }
        if (isset($filters['skip_expired'])) {
            $transactions = array_filter($transactions, function ($transaction) {
                return $transaction['expires'] > new \DateTimeImmutable() || !isset($transaction['expires']);
            });
        }
        return $transactions;
    }

    private function generateToken(string $username, array $roles): string
    {
        $data = [
            'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
            'roles' => $roles,
            'username' => $username,
        ];
        $query = base64_encode(json_encode($data));
        return 'header.' . $query . '.signature';
    }

    public function payCourse(string $code, string $userToken)
    {
        $course = $this->getCourse($code);
        $username = ((new JwtDecode())->jwtDecode($userToken))[2];
        if ($username == $this->user['username']) {
            if ($this->user['balance'] < $course['price']) {
                return [
                    'code' => 406,
                    'message' => 'MOCK:Недостаточно средств'
                ];
            }
        } elseif ($username == $this->admin['username']) {
            if ($this->admin['balance'] < $course['price']) {
                return [
                    'code' => 406,
                    'message' => 'MOCK:Недостаточно средств'
                ];
            }
        } else {
            return [
                'code' => 401,
                'message' => 'MOCK:Пользователь не авторизован'
            ];
        }
        $response = [
            'success' => true,
            'course_type' => $course['type'],
        ];
        if ($course['type'] == 'rent') {
            $response['expires_at'] = (new \DateTime())->add(new \DateInterval("P7D"));
        }
        return $response;
    }
}
