<?php

namespace Controllers;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Tests\AbstractTest;

class CourseTest extends AbstractTest
{
    public function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    /**
     * Проверяет правильный URL
     * @dataProvider urlProvider
     * @return void
     */
    public function testGetActions($url): void
    {
        $client = self::getClient();
        $client->request('GET', $url);

        $this->assertResponseOk();
    }

    public function urlProvider(): \Generator
    {
        yield ['/courses'];
        yield ['/courses/new'];
    }

    // Проверка http статусов GET
    public function testGetCoursesActions(): void
    {
        $client = self::getClient();
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        foreach ($courses as $course) {
            // страничка курсов
            $client->request('GET', '/courses/' . $course->getId());
            $this->assertResponseOk();

            // редактирование курсов
            $client->request('GET', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseOk();

            // добавление курса
            $client->request('GET', '/courses/' . $course->getId() . '/newLesson');
            $this->assertResponseOk();
        }
    }

    // Проверка http статусов POST
    public function testPostActions(): void
    {
        $client = self::getClient();
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        foreach ($courses as $course) {
            // страница добавления курса
            $client->request('POST', '/courses/new');
            $this->assertResponseOk();

            // добавление урока к курсу
            $client->request('POST', '/courses/' . $course->getId() . '/newLesson');
            $this->assertResponseOk();

            // редактирование
            $client->request('POST', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseOk();

            // удаление
            $client->request('POST', '/courses/' . $course->getId());
            $this->assertResponseRedirect();
        }
    }

    // Сравнивает число отображаемых курсов и курсов в БД
    public function testCourseCount(): void
    {
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        $this->assertCount(count($courses), $crawler->filter('.course-card'));
    }

    // Сравнивает число отображаемых уроков и уроков в БД
    public function testLessonInCourseCount(): void
    {
        $client = self::getClient();
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        foreach ($courses as $course) {
            $crawler = $client->request('GET', '/courses/' . $course->getId());
            $lessons = $course->getLessons();
            $this->assertCount(count($lessons), $crawler->filter('li'));
        }
    }

    /**
     * Проверяет неправильные URL
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
        yield ['/qwerty'];
        yield ['/courses/-1'];
        yield ['/courses/-1/newLesson'];
        yield ['/courses/-1/edit'];
    }

    // Проверка создания курса
    public function testCourseCreation(): void
    {
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');
        $countCourseBeforeAdding = count(self::getEntityManager()->getRepository(Course::class)->findAll());

        // Перейти в форму добавления
        $link = $crawler->selectLink('Добавить новый курс')->link();
        $client->click($link);
        $this->assertResponseOk();

        // Форма с пустым кодом
        $client->submitForm('Сохранить', [
            'course[code]' => ' ',
            'course[name]' => 'name',
            'course[description]' => 'Long correct course description'
        ]);
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Символьный код не может быть пустым'
        );

        // Форма с неуникальным кодом
        $client->submitForm('Сохранить', [
            'course[code]' => 'php-dev',
            'course[name]' => 'name',
            'course[description]' => 'Long correct course description'
        ]);
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Код не уникален'
        );

        // Форма с длинным кодом
        $name = '1234567890';
        $client->submitForm('Сохранить', [
            'course[code]' => str_repeat($name, 26),
            'course[name]' => 'name',
            'course[description]' => 'Long correct course description'
        ]);
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Символьный код должен быть не более 255 символов'
        );

        // Форма с коротким именем
        $client->submitForm('Сохранить', [
            'course[code]' => 'course-code',
            'course[name]' => 'nc',
            'course[description]' => 'Long correct course description'
        ]);
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название должно быть не менее 3 символов'
        );

        // Правильная форма
        $client->submitForm('Сохранить', [
            'course[code]' => 'course-code',
            'course[name]' => 'Correct name',
            'course[description]' => 'Long correct course description'
        ]);
        // Проверка редиректа на страницу курса
        $course = self::getEntityManager()->getRepository(Course::class)->findOneBy(['code' => 'course-code']);
        $this->assertSame('/courses/' . $course->getId(), $client->getResponse()->headers->get('location'));
        $crawler = $client->request('GET', '/courses');
        // Сравним курсы до добавления и после
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        $this->assertCount($countCourseBeforeAdding + 1, $courses);
    }

    // Проверка редактирования
    public function testCourseEdit(): void
    {
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');

        // Переход к курсу
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Переход к редактированию
        $link = $crawler->filter('.app_course_edit')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Код урока
        $button = $crawler->selectButton('Редактировать');
        $form = $button->form();
        $courseId = self::getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy(['code' => $form['course[code]']->getValue()])->getId();

        // Редактирование урока
        $client->submitForm('Редактировать', [
            'course[code]' => 'newCode',
            'course[name]' => 'newName',
            'course[description]' => 'newDescription'
        ]);

        // Проверить редирект
        $this->assertSame('/courses/' . $courseId, $client->getResponse()->headers->get('location'));
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
        // Проверить что имя изменилось
        $this->assertSame('newName', $crawler->filter('h1')->text());
    }

    // Проверка удаления
    public function testCourseDelete(): void
    {
        // Число курсов
        $coursesCountBeforeDelete = self::getEntityManager()->getRepository(Course::class)->findAll();
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');

        // К курсу
        $link = $crawler->filter('.app_course_show')->first()->link();
        $client->click($link);
        $this->assertResponseOk();

        // Нажатие на удалить
        $client->submitForm('Удалить');
        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();

        // Посчитать число курсов
        $coursesCountAfterDelete = count(self::getEntityManager()->getRepository(Course::class)->findAll());
        // Сравнить число после удаление и число отображаемых курсов
        $this->assertCount($coursesCountAfterDelete, $crawler->filter('.course-card'));
        // Сравнить число до удаления и после
        $this->assertCount($coursesCountAfterDelete + 1, $coursesCountBeforeDelete);
    }
}
