<?php

namespace App\Controller;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Form\RegisterType;
use App\DTO\UserDto;
use App\Security\UserAuthenticator;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_course_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
    }

    /**
     * @Route("/profile", name="app_profile")
     */
    public function profile(BillingClient $billingClient): Response
    {
        $userDto = $billingClient->currentUser($this->getUser()->getApiToken());
        $balance = $userDto->getBalance();
        return $this->render('security/profile.html.twig', ['user' => $this->getUser(), 'balance' => $balance]);
    }

    /**
     * @Route("/register", name="app_register")
     * @throws \JsonException
     */
    public function register(
        Request $request,
        UserAuthenticatorInterface $authenticator,
        BillingClient $billingClient,
        UserAuthenticator $formAuthenticator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }
        $error = [];
        $dtoRequest = new UserDto();
        $form = $this->createForm(RegisterType::class, $dtoRequest);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $billingClient->register(json_encode([
                    'username' => $form->get('username')->getData(),
                    'password' => $form->get('password')->getData()]));
            } catch (BillingException | BillingUnavailableException $e) {
                $error[] = json_decode($e->getMessage(), true);
                return $this->render('security/register.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $error
                ]);
            }
            return $authenticator->authenticateUser(
                $user,
                $formAuthenticator,
                $request
            );
        }
        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
            'errors' => ''
        ]);
    }
}
