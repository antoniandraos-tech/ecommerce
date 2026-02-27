<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
    
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/register', name: 'app_register')]
public function register(
    Request $request, 
    UserPasswordHasherInterface $passwordHasher, 
    EntityManagerInterface $entityManager
): Response
{
    $user = new User();
    $form = $this->createForm(UserType::class, $user);
    $form->handleRequest($request);

   if ($form->isSubmitted() && $form->isValid()) {
    $plainPassword = $form->get('password')->getData();
    
    $hashedPassword = $passwordHasher->hashPassword(
        $user,
        $plainPassword
    );
    $user->setPassword($hashedPassword);

    $entityManager->persist($user);
    $entityManager->flush();

    $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');

    return $this->redirectToRoute('app_login');
}
    return $this->render('user/new.html.twig', [ 
        'user' => $user,
        'form' => $form,
    ]);
}

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}