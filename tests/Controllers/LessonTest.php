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

    // ПРоверка http статусов POST
    public function testPostLessonsActions(): void
    {
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
        $client = self::getClient();
        $client->request('GET', $url);

        $this->assertResponseCode('404');
    }

    public function wrongUrlProvider(): \Generator
    {
        yield ['/asd'];
        yield ['/lessons/-1'];
        yield ['/lessons/-1/edit'];
    }

    // ПРоверка на создание урока
    public function testLessonCreation(): void
    {
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
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Удаление и редирект
        $client->submitForm('Удалить');
        self::assertSame('/courses/' . $course->getId(), $client->getResponse()->headers->get('location'));
        $crawler = $client->followRedirect();
        // Посчитать уроки
        self::assertCount($lessonCountBeforeDeleting - 1, $crawler->filter('.list-group-item'));
    }

    public function getFixtures(): array
    {
        return [CourseFixtures::class];
    }
}
