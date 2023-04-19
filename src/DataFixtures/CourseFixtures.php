<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $frontend_developer = new Course();
        $frontend_developer
            ->setName('Фронтенд-разработчик')
            ->setDescription('Научитесь создавать функциональные и удобные ' .
                'пользовательские интерфейсы сайтов и приложений')
            ->setCode('frontend-dev');
        $frontend_developer->addLesson($this->createLesson(
            '1',
            'Верстка',
            'Основы верстки и позиционирования'
        ));
        $frontend_developer->addLesson($this->createLesson(
            '2',
            'Веб-программирование',
            'Основы веб-программирования'
        ));
        $frontend_developer->addLesson($this->createLesson(
            '3',
            'JavaScript',
            'Профессиональный JavaScript'
        ));
        $frontend_developer->addLesson($this->createLesson(
            '4',
            'Разработка браузерных приложений',
            'Устройство операционных систем (администрирование, процессы, файловая система) ' .
            'Подключение к обучению подкастов, книг, онлайн-мероприятий, ведение блога ' .
            'Командная работа в Git ' .
            'REST API, Очереди, Background Jobs'
        ));
        $frontend_developer->addLesson($this->createLesson(
            '5',
            'Разработка React приложений',
            'Пробные собеседования ' .
            'Reach Hooks: useState, useEffect, useRef ' .
            'Базовый Webpack'
        ));
        $manager->persist($frontend_developer);

        $data_analyst = new Course();
        $data_analyst
            ->setName('Аналитик данных')
            ->setDescription('Научитесь понимать основные метрики компаний и самостоятельно считать их, ' .
                'используя SQL и Google Sheets. Проводитее когортный анализ и стройте прогнозы, ' .
                'визуализируя данные с помощью Superset и библиотек Python. ' .
                'Делайте выводы на основе исследования, обосновывайте их и помогайте бизнеус расти')
            ->setCode('data-analyst');
        $data_analyst->addLesson($this->createLesson(
            '1',
            'SQL',
            'Умение применять SQL для анализа данных открывает перед аналитиком огромные '.
            'возможности, дает ему инструмент самостоятельно проверять и обосновывать почти любую гипотезу'
        ));
        $data_analyst->addLesson($this->createLesson(
            '2',
            'Выбор и расчёт метрик',
            'Разные бизнес-задачи можно анализировать с помощью соответствующих '.
            'метрик: LTV, ROI, Retention, Churn rate и другие. Понимание этих метрик и умение '.
            'их рассчитывать очень важно для любого аналитика'
        ));
        $data_analyst->addLesson($this->createLesson(
            '3',
            'Визуализация в SUPERSET',
            'Чтобы принимать бизнес-решения, нужно уметь представлять данные в '.
            'виде наглядных отчетов, удобных для презентации и анализа'
        ));
        $data_analyst->addLesson($this->createLesson(
            '4',
            'Интсрументы для вычислений',
            'Даже если бизнес компании описывается миллионами записей данных, '.
            'знание Google Sheets, языков SQL и Python позволяет легко выполнить любые виды '.
            'расчетов и получить все нужные метрики'
        ));
        $manager->persist($data_analyst);

        $python_developer = new Course();
        $python_developer
            ->setName('Python-разработчик')
            ->setDescription('Изучите Python — язык с простым и понятным синтаксисом. ' .
                'Научитесь работать с сетевыми запросами и проектировать архитектуру приложений. ' .
                'Освойте самый популярный веб-фреймворк Django, чтобы быстро создавать безопасные ' .
                'и поддерживаемые сайты')
            ->setCode('python-dev');

        $python_developer->addLesson($this->createLesson(
            '1',
            'Python',
            'Простой и эффективный язык, применимый в совершенно разных сферах. '.
            'На Python пишут игры, веб-приложения, утилиты, проводят научные вычисления и '.
            'автоматизируют процессы'
        ));
        $python_developer->addLesson($this->createLesson(
            '2',
            'Фреймворк (Django)',
            'Задает архитектуру проекта. Решает типовые задачи за программиста. '.
            'Значительно сокращает количество кода и автоматизирует рутину'
        ));
        $python_developer->addLesson($this->createLesson(
            '3',
            'Алгоритмы и структуры данных',
            'Понимание этих принципов позволяет писать более продуктивный '.
            'и аккуратный код, видеть разные варианты решения задачи и сравнивать их по эффективности'
        ));
        $python_developer->addLesson($this->createLesson(
            '4',
            'Архитектура',
            'Один и тот же код можно написать бесконечным количеством способов. '.
            'И только от разработчика зависит, как организовать код так, чтобы его можно было '.
            'легко анализировать и изменять'
        ));
        $python_developer->addLesson($this->createLesson(
            '5',
            'Инфраструктура',
            'Программирование — это не только код, но и инфраструктура. 
            Понимание того, как работают сопутствующие инструменты: командная строка, Poetry, 
            Git — позволит быстро подготовиться к старту любого проекта'
        ));
        $manager->persist($python_developer);

        $java_developer = new Course();
        $java_developer
            ->setName('Java-разработчик')
            ->setDescription('Изучите на курсах кроссплатформенный язык программирования Java, ' .
                'который любит крупный бизнес. Научитесь подбирать правильные структуры для хранения и ' .
                'обработки данных. Познакомьтесь с автоматизированным тестированием и напишите свои первые ' .
                'модульные тесты. Прокачайтесь в ООП и собирайте веб-приложения с помощью Spring Boot')
            ->setCode('java-dev');

        $java_developer->addLesson($this->createLesson(
            '1',
            'JAVA_CORE',
            'Основы языка Java. Познакомитесь с базовыми конструкциями, типами данных, '.
            'принципами ООП. Научитесь работать с коллекциями, классами и объектами',
        ));
        $java_developer->addLesson($this->createLesson(
            '2',
            'SPRING BOOT',
            'Самый популярный в коммерческой разработке фреймворк, упрощающий работу '.
            'и значительно сокращающий количество кода',
        ));
        $java_developer->addLesson($this->createLesson(
            '3',
            'JUnit и Mockito',
            'Автоматизированные тесты — неотъемлемая часть профессиональной разработки. '.
            'JUnit и Mockito — инструменты Java-разработчиков для проверки работоспособности приложений',
        ));
        $java_developer->addLesson($this->createLesson(
            '4',
            'MAVEN',
            'Система управления зависимостями и сборки проектов. С помощью Maven можно '.
            'автоматически загружать и управлять зависимостями, настраивать сборку проекта, '.
            'создавать документацию, тестировать и публиковать проект',
        ));
        $java_developer->addLesson($this->createLesson(
            '5',
            'GIT',
            'Программирование — это не только код, но и инфраструктура. '.
            'Понимание работы сопутствующих инструментов — командной строки, Gradle и '.
            'Git — позволит быстро подготовиться к старту любого проекта',
        ));
        $manager->persist($java_developer);

        $php_developer = new Course();
        $php_developer
            ->setName('PHP-разработчик')
            ->setDescription('Изучите гибкий и масштабируемый PHP. ' .
                'Познакомьтесь с языками веб-разработки HTML и CSS, чтобы понимать, как устроены ' .
                'интернет-страницы. Разберитесь в базах данных и научитесь управлять ими с помощью SQL. ' .
                'Освойте самый популярный фреймворк PHP — Laravel, чтобы быстро писать код и ' .
                'автоматизировать рутину')
            ->setCode('php-dev');

        $php_developer->addLesson($this->createLesson(
            '1',
            'PHP',
            'Один из самых популярных языков, на котором написано более 80% сайтов в интернете',
        ));
        $php_developer->addLesson($this->createLesson(
            '2',
            'HTML и CSS',
            'Языки создания веб-страниц. Описывают их структуру (расположение блоков) '.
            'и внешний вид. Отвечают за форматирование текста',
        ));
        $php_developer->addLesson($this->createLesson(
            '3',
            'Алгоритмы и структуры данных',
            'Любая программа — это последовательность шагов, выполняемых над данными. '.
            'Способ организации данных сильно влияет на удобство работы',
        ));
        $php_developer->addLesson($this->createLesson(
            '4',
            'Качество',
            'Автоматизированные тесты — неотъемлемая часть профессиональной разработки. '.
            'Хорошо написанные тесты значительно ускоряют разработку, позволяют быстро находить ошибки '.
            'и исправлять их',
        ));
        $php_developer->addLesson($this->createLesson(
            '5',
            'Архитектура',
            'Один и тот же код можно написать бесконечным количеством способов. '.
            'И только от разработчика зависит, как организовать код так, чтобы его можно было '.
            'легко анализировать и изменять',
        ));
        $manager->persist($php_developer);

        $manager->flush();
    }

    public function createLesson(int $number, string $name, string $description): Lesson
    {
        $lesson = new Lesson();
        $lesson->setNumber($number)
            ->setName($name)
            ->setDescription($description);
        return $lesson;
    }
}
