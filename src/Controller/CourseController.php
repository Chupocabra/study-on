<?php

namespace App\Controller;

use App\DTO\CourseDTO;
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
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/courses")
 */
class CourseController extends AbstractController
{
    private CourseRepository $courseRepository;

    public function __construct(CourseRepository $courseRepository)
    {
        $this->courseRepository = $courseRepository;
    }

    /**
     * @Route("", name="app_course_index", methods={"GET"})
     */
    public function index(BillingClient $client): Response
    {
        try {
            $billingCourses = $client->getCourses();
            $balance = 0;
            if ($this->getUser()) {
                /** @var User $user */
                $user = $this->getUser();
                $transactions = $client->getTransactions($user->getApiToken(), [
                    'type' => 'payment',
                    'skip_expired' => true
                ]);
                foreach ($transactions as $transaction) {
                    $transactionsInfo[$transaction['course_code']] = $transaction;
                }
                $balance = $client->currentUser($user->getApiToken())->getBalance();
            }
            $userCourses = [];
            $anotherCourses = [];
            foreach ($billingCourses as $course) {
                if (isset($transactionsInfo[$course['code']])) {
                    $userCourses[$course['code']] = $course;
                    if ($course['type'] === 'rent') {
                        $userCourses[$course['code']]['expire'] = $transactionsInfo[$course['code']]['expires']['date'];
                    }
                } else {
                    $anotherCourses[$course['code']] = $course;
                }
            }
            return $this->render('course/index.html.twig', [
                'courses' => $this->courseRepository->findAll(),
                'balance' => $balance,
                'userCourses' => $userCourses,
                'anotherCourses' => $anotherCourses,
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
    public function new(Request $request, BillingClient $client, SerializerInterface $serializer): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            try {
                $data = new CourseDTO();
                $data->price = $form->get('price')->getData();
                $data->type = $form->get('type')->getData();
                $data->title = $form->get('name')->getData();
                $data->code = $form->get('code')->getData();
                $response = $client->addCourse($user->getApiToken(), $serializer->serialize($data, 'json'));
                if ($response['success']) {
                    $this->courseRepository->add($course, true);
                    return $this->redirectToRoute(
                        'app_course_show',
                        ['id' => $course->getId()],
                        Response::HTTP_SEE_OTHER
                    );
                } else {
                    $this->addFlash('error', $response['message']);
                    return $this->renderForm('course/new.html.twig', [
                        'course' => $course,
                        'form' => $form,
                    ]);
                }
            } catch (BillingException | BillingUnavailableException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->renderForm('course/new.html.twig', [
                    'course' => $course,
                    'form' => $form,
                ]);
            }
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
    public function edit(
        Request $request,
        Course $course,
        BillingClient $client,
        SerializerInterface $serializer
    ): Response {
        try {
            $billingCourse = $client->getCourse($course->getCode());
        } catch (BillingException | BillingUnavailableException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->renderForm('course/edit.html.twig', [
                'course' => $course,
            ]);
        }
        $form = $this->createForm(CourseType::class, $course);
        $form->get('type')->setData($billingCourse['type']);
        $form->get('price')->setData($billingCourse['price']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var User $user */
                $user = $this->getUser();
                $data = new CourseDTO();
                $data->price = $form->get('price')->getData();
                $data->type = $form->get('type')->getData();
                $data->title = $form->get('name')->getData();
                $data->code = $form->get('code')->getData();
                $response = $client->editCourse(
                    $user->getApiToken(),
                    $billingCourse['code'],
                    $serializer->serialize($data, 'json')
                );
                if ($response['success']) {
                    $this->courseRepository->add($course, true);
                    return $this->redirectToRoute(
                        'app_course_show',
                        ['id' => $course->getId()],
                        Response::HTTP_SEE_OTHER
                    );
                } else {
                    $this->addFlash('error', $response['message']);
                    return $this->renderForm('course/new.html.twig', [
                        'course' => $course,
                        'form' => $form,
                    ]);
                }
            } catch (BillingException | BillingUnavailableException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->renderForm('course/edit.html.twig', [
                    'course' => $course,
                    'form' => $form,
                ]);
            }
        }
        return $this->renderForm('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_course_delete", methods={"POST"})
     */
    public function delete(Request $request, Course $course): Response
    {
        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            $this->courseRepository->remove($course, true);
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
