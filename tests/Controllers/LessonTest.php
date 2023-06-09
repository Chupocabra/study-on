<?php

namespace Controllers;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;

class LessonTest extends AbstractTest
{
    // Проверка http статусов GET
    public function testGetLessonsActions(): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $lessons = self::getEntityManager()->getRepository(Lesson::class)->findAll();
        foreach ($lessons as $lesson) {
            // show lesson
            $client->request('GET', '/lessons/' . $lesson->getId());
            $this->assertResponseOk();
            // edit lesson
            $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
            $this->assertResponseOk();
        }
    }

    // Проверка http статусов POST
    public function testPostLessonsActions(): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $lessons = self::getEntityManager()->getRepository(Lesson::class)->findAll();
        foreach ($lessons as $lesson) {
            // show lesson
            $client->request('POST', '/lessons/' . $lesson->getId());
            $this->assertResponseRedirect();
            // edit lesson
            $client->request('POST', '/lessons/' . $lesson->getId() . '/edit');
            $this->assertResponseOk();
        }
    }

    // Проверка на имя курса
    public function testCourseName(): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $lessons = self::getEntityManager()->getRepository(Lesson::class)->findAll();
        foreach ($lessons as $lesson) {
            $crawler = $client->request('GET', '/lessons/' . $lesson->getId());
            $course_name = $lesson->getCourse()->getName();
            $this->assertEquals($course_name, $crawler->filter('.course-name')->innerText());
        }
    }

    /**
     * Проверка на неправильный URL
     * @dataProvider wrongUrlProvider
     * @return void
     */
    public function testWrongURL($url): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $client->request('GET', $url);

        $this->assertResponseCode('404');
    }

    public function wrongUrlProvider(): \Generator
    {
        yield ['/lessons/-1'];
        yield ['/lessons/-1/edit'];
    }

    // Проверить форму с пустым номером
    public function testEmptyNumber(): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');

        // К курсу
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $lessonCountBeforeAdding = count($crawler->filter('.list-group-item'));
        $this->assertResponseOk();

        // К добавлению урока
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Форма с пустым номером
        $client->submitForm('Сохранить', [
            'lesson[number]' => '',
            'lesson[name]' => 'name',
            'lesson[description]' => 'actions and description'
        ]);
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Порядковый номер урока не может быть пустым'
        );
    }

    // Проверить форму с номером > 10000
    public function testBigNumber(): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');

        // К курсу
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $lessonCountBeforeAdding = count($crawler->filter('.list-group-item'));
        $this->assertResponseOk();

        // К добавлению урока
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Форма с номером > 10000
        $client->submitForm('Сохранить', [
            'lesson[number]' => '11111',
            'lesson[name]' => 'name',
            'lesson[description]' => 'actions and description'
        ]);
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Значение поля должно быть от 1 до 10000'
        );
    }

    // Проверить форму с пустым именем
    public function testEmptyName(): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');

        // К курсу
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $lessonCountBeforeAdding = count($crawler->filter('.list-group-item'));
        $this->assertResponseOk();

        // К добавлению урока
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Форма с пустым именем
        $client->submitForm('Сохранить', [
            'lesson[number]' => '2',
            'lesson[name]' => '',
            'lesson[description]' => 'actions and description'
        ]);
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название не может быть пустым'
        );
    }

    // Проверка формы с коротким именем
    public function testShortName(): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');

        // К курсу
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $lessonCountBeforeAdding = count($crawler->filter('.list-group-item'));
        $this->assertResponseOk();

        // К добавлению урока
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Форма с коротким именем
        $client->submitForm('Сохранить', [
            'lesson[number]' => '2',
            'lesson[name]' => 'n',
            'lesson[description]' => 'actions and description'
        ]);
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название должно содержать более 3 символов'
        );
    }

    // Проверка формы с длинным именем
    public function testLongName(): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');

        // К курсу
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $lessonCountBeforeAdding = count($crawler->filter('.list-group-item'));
        $this->assertResponseOk();

        // К добавлению урока
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Форма с длинным именем
        $name = '0987654321';
        $client->submitForm('Сохранить', [
            'lesson[number]' => '2',
            'lesson[name]' => str_repeat($name, 26),
            'lesson[description]' => 'actions and description'
        ]);
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название должно быть не более 255 символов'
        );
    }

    // Проферка на форму с некорректным номером
    public function testIncorrectNumber(): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');

        // К курсу
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $lessonCountBeforeAdding = count($crawler->filter('.list-group-item'));
        $this->assertResponseOk();

        // К добавлению урока
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Форма с некорректным номером
        $client->submitForm('Сохранить', [
            'lesson[number]' => 'qwerty',
            'lesson[name]' => 'name',
            'lesson[description]' => 'actions and description'
        ]);
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Значение недопустимо'
        );
    }

    // Проверка на форму с пустым описанием
    public function testEmptyDescription(): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');

        // К курсу
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $lessonCountBeforeAdding = count($crawler->filter('.list-group-item'));
        $this->assertResponseOk();

        // К добавлению урока
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Форма с пустым описанием
        $client->submitForm('Сохранить', [
            'lesson[number]' => '2',
            'lesson[name]' => 'name',
            'lesson[description]' => '  '
        ]);
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Поле не может быть пустым'
        );
    }

    // Проверка на создание урока
    public function testLessonCreation(): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');

        // К курсу
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $lessonCountBeforeAdding = count($crawler->filter('.list-group-item'));
        $this->assertResponseOk();

        // К добавлению урока
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Правильная форма
        $client->submitForm('Сохранить', [
            'lesson[number]' => '10000',
            'lesson[name]' => 'Last lesson',
            'lesson[description]' => 'Descriptions. Asd. Qwe.'
        ]);
        $button = $crawler->selectButton('Сохранить');
        $form = $button->form();

        // Курс урока
        $course = self::getEntityManager()->getRepository(Course::class)
            ->findOneBy(['id' => $form['lesson[course]']->getValue()]);
        // Проверка редиректа
        $this->assertSame('/courses/' . $course->getId(), $client->getResponse()->headers->get('location'));
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        // Сравнить число до добавления и число отображаемых
        $this->assertCount($lessonCountBeforeAdding + 1, $crawler->filter('.list-group-item'));
        // Проверить имя последнего урока
        $this->assertSame($crawler->filter('.list-group-item')->last()->text(), '10000) Last lesson');
    }

    // Проверить редактирование
    public function testLessonEdit(): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');

        // К курсы
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // К уроку
        $link = $crawler->filter('.lesson')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // К редактированию
        $link = $crawler->selectLink('Редактировать')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Новые данные
        $client->submitForm('Редактировать', [
            'lesson[number]' => '10000',
            'lesson[name]' => 'Last lesson',
            'lesson[description]' => 'Descriptions. Asd. Qwe.'
        ]);
        $button = $crawler->selectButton('Редактировать');
        $form = $button->form();

        // Курс урока
        $course = self::getEntityManager()->getRepository(Course::class)
            ->findOneBy(['id' => $form['lesson[course]']->getValue()]);

        // Проверить редирект
        $this->assertSame('/courses/' . $course->getId(), $client->getResponse()->headers->get('location'));
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
        // Проверить текст последнего урока
        $this->assertSame($crawler->filter('.lesson')->last()->text(), '10000) Last lesson');
    }

    // Проверить удаление
    public function testLessonDelete(): void
    {
        $this->loginAsAdmin();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');

        // К курсу
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // К уроку
        $link = $crawler->filter('.lesson')->first()->link();
        $newCrawler = $client->click($link);
        $this->assertResponseOk();

        // К редактированию
        $link = $newCrawler->selectLink('Редактировать')->link();
        $newCrawler = $client->click($link);
        $this->assertResponseOk();
        $button = $newCrawler->selectButton('Редактировать');
        $form = $button->form();

        // Узнать курс урока
        $course = self::getEntityManager()->getRepository(Course::class)
            ->findOneBy(['id' => $form['lesson[course]']->getValue()]);
        // Посчитать число уроков
        $lessonCountBeforeDeleting = count($course->getLessons());

        // К уроку
        $link = $crawler->filter('.lesson')->first()->link();
        $client->click($link);
        $this->assertResponseOk();

        // Удаление и редирект
        $client->submitForm('Удалить');
        self::assertSame('/courses/' . $course->getId(), $client->getResponse()->headers->get('location'));
        $crawler = $client->followRedirect();
        // Посчитать уроки
        self::assertCount($lessonCountBeforeDeleting - 1, $crawler->filter('.list-group-item'));
    }
    // User пытается изменить урок
    public function testLessonEditByUser()
    {
        $this->loginAsAdmin(false);
        $client = self::getClient();
        $client->request('POST', '/lessons/' .
            self::getEntityManager()->getRepository(Lesson::class)->findAll()[0]->getId() . '/edit');
        $this->assertResponseCode(403);
    }
    // User пытается удалить урок
    public function testLessonDeleteByUser()
    {
        $this->loginAsAdmin(false);
        $client = self::getClient();
        $client->request('POST', '/lessons/' .
            self::getEntityManager()->getRepository(Lesson::class)->findAll()[0]->getId());
        $this->assertResponseCode(403);
    }
    // Страницы, недоступные публично, неавторизованный пользователь
    public function testNonAuthPages()
    {
        $client = self::getClient();
        // Просмотр урока
        $client->request('GET', '/lessons/100');
        $this->assertResponseRedirect();
        $this->assertSame('/login', $client->getResponse()->headers->get('location'));
        // Переход в профиль
        $client->request('GET', '/profile');
        $this->assertResponseRedirect();
        $this->assertSame('/login', $client->getResponse()->headers->get('location'));
    }
    // Страницы, недоступные публично, авторизованный пользователь
    public function testAuthPages()
    {
        $this->loginAsAdmin(false);
        $freeCourseId = self::getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy(['code' => 'frontend-dev'])->getId();
        // Переход к уроку
        $client = self::getClient();
        $crawler = $client->request('GET', "/courses/$freeCourseId");
        $this->assertResponseOk();
//        $link = $crawler->filter('.app_course_show')->first()->link();
//        $crawler = $client->click($link);
        $link = $crawler->filter('.lesson')->first()->link();
        $client->click($link);
        $this->assertResponseOk();
        // Переход в профиль
        $client->request('GET', '/profile');
        $this->assertResponseOk();
    }
    public function loginAsAdmin($admin = true)
    {
        $login = new SecurityTest();
        return $login->login($admin);
    }

    public function getFixtures(): array
    {
        return [CourseFixtures::class];
    }
}
