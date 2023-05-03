<?php

namespace Controllers;

use App\Tests\AbstractTest;
use App\Tests\Mock\BillingClientMock;
use Symfony\Component\Serializer\SerializerInterface;

class SecurityTest extends AbstractTest
{
    private array $userCredentials = [
        'email' => 'my_user@email.com',
        'password' => 'user'
    ];

    private array $adminCredentials = [
        'email' => 'my_admin@email.com',
        'password' => 'admin'
    ];

    // Авторизация и выход
    public function testAuthLogout()
    {
        $client = $this->billingClient();

        $crawler = $client->request('GET', '/courses');
        $link = $crawler->selectLink('Вход')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $formButton = $crawler->selectButton('Авторизация');
        $form = $formButton->form([
            'email' => $this->userCredentials['email'],
            'password' => $this->userCredentials['password']
        ]);
        $client->submit($form);
        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();
        $this->assertEquals('/courses', $client->getRequest()->getPathInfo());

        $link = $crawler->selectLink('Выход')->link();
        $client->click($link);

        $this->assertResponseRedirect();
        $client->followRedirect();
    }

    // Авторизация с неправильными данным
    public function testAuthWithErrors()
    {
        $client = $this->billingClient();

        $crawler = $client->request('GET', '/courses');
        $link = $crawler->selectLink('Вход')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $formButton = $crawler->selectButton('Авторизация');
        $form = $formButton->form([
            'email' => $this->userCredentials['email'],
            'password' => $this->userCredentials['password'] . '123'
        ]);
        $client->submit($form);
        $this->assertResponseRedirect();
        $client->followRedirect();
        $this->assertSelectorTextContains(
            '.alert.alert-danger',
            'Ошибка авторизации.'
        );
    }

    // Регистрация на зарегистрированную почту
    public function testRegisterUsedEmail()
    {
        $client = $this->billingClient();
        $crawler = $client->request('GET', '/courses');
        $link = $crawler->selectLink('Регистрация')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $formButton = $crawler->selectButton('Регистрация');
        $form = $formButton->form([
            'register[username]' => $this->userCredentials['email'],
            'register[password][first]' => '123456',
            'register[password][second]' => '123456',
        ]);
        $client->submit($form);
        $this->assertEquals('/register', $client->getRequest()->getPathInfo());
        $this->assertSelectorTextContains(
            '.alert.alert-danger',
            'Пользователь с такой почтой уже существует'
        );
    }

    // Регистрация короткий пароль
    public function testRegisterShortPassword()
    {
        $client = $this->billingClient();
        $crawler = $client->request('GET', '/courses');
        $link = $crawler->selectLink('Регистрация')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $formButton = $crawler->selectButton('Регистрация');
        $form = $formButton->form([
            'register[username]' => 'new' . $this->userCredentials['email'],
            'register[password][first]' => '12',
            'register[password][second]' => '12',
        ]);
        $client->submit($form);
        $this->assertEquals('/register', $client->getRequest()->getPathInfo());
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Минимальная длина пароля 6 символов'
        );
    }

    // Регистрация длинный пароль
    public function testRegisterLongPassword()
    {
        $client = $this->billingClient();
        $crawler = $client->request('GET', '/courses');
        $link = $crawler->selectLink('Регистрация')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $formButton = $crawler->selectButton('Регистрация');
        $form = $formButton->form([
            'register[username]' => 'new' . $this->userCredentials['email'],
            'register[password][first]' => '1234567890qwertyuiop',
            'register[password][second]' => '1234567890qwertyuiop',
        ]);
        $client->submit($form);
        $this->assertEquals('/register', $client->getRequest()->getPathInfo());
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Максимальная длина пароля 16 символов'
        );
    }
    // Регистрация пустой пароль
    public function testRegisterEmptyPassword()
    {
        $client = $this->billingClient();
        $crawler = $client->request('GET', '/courses');
        $link = $crawler->selectLink('Регистрация')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $formButton = $crawler->selectButton('Регистрация');
        $form = $formButton->form([
            'register[username]' => 'new' . $this->userCredentials['email'],
            'register[password][first]' => '',
            'register[password][second]' => '',
        ]);
        $client->submit($form);
        $this->assertEquals('/register', $client->getRequest()->getPathInfo());
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Введите пароль пользователя'
        );
    }
    // Регистрация разные пароли
    public function testRegisterDifferentPasswords()
    {
        $client = $this->billingClient();
        $crawler = $client->request('GET', '/courses');
        $link = $crawler->selectLink('Регистрация')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $formButton = $crawler->selectButton('Регистрация');
        $form = $formButton->form([
            'register[username]' => 'new' . $this->userCredentials['email'],
            'register[password][first]' => '123456',
            'register[password][second]' => '789012',
        ]);
        $client->submit($form);
        $this->assertEquals('/register', $client->getRequest()->getPathInfo());
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Пароли не совпадают'
        );
    }
    // Регистрация невалидная почта
    public function testRegisterNotValidEmail()
    {
        $client = $this->billingClient();
        $crawler = $client->request('GET', '/courses');
        $link = $crawler->selectLink('Регистрация')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $formButton = $crawler->selectButton('Регистрация');
        $form = $formButton->form([
            'register[username]' => 'newmail',
            'register[password][first]' => '123456',
            'register[password][second]' => '123456',
        ]);
        $client->submit($form);
        $this->assertEquals('/register', $client->getRequest()->getPathInfo());
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Почта не соответствует формату'
        );
    }
    // Регистрация и выход
    public function testRegisterLogout()
    {
        $client = $this->billingClient();
        $crawler = $client->request('GET', '/courses');
        $link = $crawler->selectLink('Регистрация')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $formButton = $crawler->selectButton('Регистрация');
        $form = $formButton->form([
            'register[username]' => 'new' . $this->userCredentials['email'],
            'register[password][first]' => '123456',
            'register[password][second]' => '123456',
        ]);
        $client->submit($form);
        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();
        $this->assertEquals('/courses', $client->getRequest()->getPathInfo());

        $link = $crawler->selectLink('Выход')->link();
        $client->click($link);

        $this->assertResponseRedirect();
        $client->followRedirect();
    }

    public function login(bool $admin)
    {
        $client = $this->getClient();
        $client->disableReboot();
        $client->getContainer()->set(
            'App\Service\BillingClient',
            new BillingClientMock($client->getContainer()->get(SerializerInterface::class))
        );

        $crawler = $client->request('GET', '/courses');
        $link = $crawler->selectLink('Вход')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();
        $formButton = $crawler->selectButton('Авторизация');
        if ($admin) {
            $form = $formButton->form([
                'email' => $this->adminCredentials['email'],
                'password' => $this->adminCredentials['password']
            ]);
        } else {
            $form = $formButton->form([
                'email' => $this->userCredentials['email'],
                'password' => $this->userCredentials['password']
            ]);
        }
        $client->submit($form);
        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();
        $this->assertEquals('/courses', $client->getRequest()->getPathInfo());
        return $crawler;
    }

    public function billingClient()
    {
        $client = $this->getClient();
        $client->disableReboot();
        $client->getContainer()->set(
            'App\Service\BillingClient',
            new BillingClientMock($client->getContainer()->get(SerializerInterface::class))
        );
        return $client;
    }
}
