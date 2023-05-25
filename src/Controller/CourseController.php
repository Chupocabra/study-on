<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Form\CourseType;
use App\Form\LessonType;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authenticator\RemoteUserAuthenticator;

/**
 * @Route("/courses")
 */
class CourseController extends AbstractController
{
    /**
     * @Route("", name="app_course_index", methods={"GET"})
     */
    public function index(CourseRepository $courseRepository, BillingClient $client): Response
    {
        try {
            $billingCourses = $client->getCourses();
            $balance = 0;
            if ($this->getUser()) {
                /** @var User $user */
                $user = $this->getUser();
                $transactions = $client->getTransactions($user->getApiToken(), [
                    'type' => 'payment', 'skip_expired' => true]);
                foreach ($transactions as $transaction) {
                    $transactionsInfo[$transaction['course_code']] = $transaction;
                }
                $userDto = $client->currentUser($user->getApiToken());
                $balance = $userDto->getBalance();
            }
            $info = [];
            foreach ($billingCourses as $course) {
                if (isset($transactionsInfo[$course['code']])) {
                    if ($course['type'] === 'rent') {
                        $type = 'Арендован до ' .
                            date_format(date_create($transactionsInfo[$course['code']]['expires']['date']), 'd.m.Y');
                    } else {
                        $type = 'Приобретен';
                    }
                    $price = 0;
                    $title = '';
                    $style = 'text-primary';
                } else {
                    if ($course['type'] === 'free') {
                        $type = 'Бесплатный курс';
                        $title = '';
                        $price = 0;
                    } elseif ($course['type'] === 'rent') {
                        $type = 'Курс в аренду';
                        $price = $course['price'];
                        $title = 'Арендовать';
                    } else {
                        $type = 'Платный курс';
                        $price = $course['price'];
                        $title = 'Купить';
                    }
                    $style = '';
                }
                $info[$course['code']] = [
                    'type' => $type,
                    'price' => $price,
                    'style' => $style,
                    'title' => $title
                ];
            }
            return $this->render('course/index.html.twig', [
                'courses' => $courseRepository->findAll(),
                'info' => $info,
                'balance' => $balance,
            ]);
        } catch (BillingException | BillingUnavailableException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->render('course/index.html.twig', [
                'courses' => [],
            ]);
        }
    }

    /**
     * @Route("/new", name="app_course_new", methods={"GET", "POST"})
     */
    public function new(Request $request, CourseRepository $courseRepository): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $courseRepository->add($course, true);

            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_course_show", methods={"GET"})
     */
    public function show(Course $course, BillingClient $client): Response
    {
        try {
            $courseInfo = $client->getCourse($course->getCode());
            if (is_null($courseInfo) || $courseInfo['type'] === 'free') {
                return $this->render('course/show.html.twig', [
                    'course' => $course
                ]);
            }
            /** @var User $user */
            $user = $this->getUser();
            if (!$user) {
                $this->addFlash('error', 'Вам необходимо зарегистрироваться, чтобы получить доступ к этому курсу.');
                return $this->redirectToRoute('app_login');
            }
            $transaction = $client->getTransactions($user->getApiToken(), [
                'type' => 'payment',
                'course_code' => $course->getCode(),
                'skip_expired' => true,
            ]);
            if ($transaction || $this->isGranted('ROLE_SUPER_ADMIN')) {
                return $this->render('course/show.html.twig', [
                    'course' => $course,
                ]);
            }
            throw new BillingException('Вам не доступен этот курс');
        } catch (BillingException | BillingUnavailableException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_course_index');
        }
    }

    /**
     * @Route("/{id}/edit", name="app_course_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Course $course, CourseRepository $courseRepository): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $courseRepository->add($course, true);

            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_course_delete", methods={"POST"})
     */
    public function delete(Request $request, Course $course, CourseRepository $courseRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            $courseRepository->remove($course, true);
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/newLesson", name="app_lesson_new", methods={"GET", "POST"})
     */
    public function newLesson(Request $request, Course $course, LessonRepository $lessonRepository): Response
    {
        $lesson = new Lesson();
        $lesson->setCourse($course);
        $form = $this->createForm(LessonType::class, $lesson, [
            'course' => $course,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lessonRepository->add($lesson, true);

            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/pay", name="app_course_pay", methods={"GET"})
     */
    public function payForCourse(Course $course, BillingClient $billingClient): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Необходимо зарегистрироваться для покупки курсов.');
            return $this->redirectToRoute('app_login');
        }
        try {
            $response = $billingClient->payCourse($course->getCode(), $user->getApiToken());
            if (isset($response['success']) && $response['success']) {
                $this->addFlash('success', 'Вы приобрели курс');
            } else {
                $this->addFlash('error', $response['message']);
            }
        } catch (BillingException | BillingUnavailableException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('app_course_index');
    }
}
