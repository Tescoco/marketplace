<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\AdsType;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController 
{
    #[Route('/users', name: 'users')]
    #[IsGranted('ROLE_ADMIN')]
    public function list(EntityManagerInterface $em): Response 
    {
        $user = $this->getUser();

        $users = $em->getRepository(User::class)->findAll();

        return $this->render('users/list.html.twig', [
            'users' => $users,
            'currentUser' => $user
        ]);
    }

    #[Route('/user/new', name: 'new_user')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'User created successfully');
            return $this->redirectToRoute('users');
        }

        return $this->render('users/new.html.twig',[
            'form' => $form->createView()
        ]);
    }
    #[Route('/user/edit/{id}', name: 'edit_user')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(int $id, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $originalPassword = $user->getPassword();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Only hash and update password if a new one was provided
            $newPassword = $form->get('password')->getData();
            if (!empty($newPassword)) {
                // Validate password length
                if (strlen($newPassword) < 6) {
                    $this->addFlash('error', 'Password must be at least 6 characters long');
                    return $this->render('users/edit.html.twig', [
                        'form' => $form->createView(),
                        'user' => $user
                    ]);
                }
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
            } else {
                // Keep the original password
                $user->setPassword($originalPassword);
            }

            $em->flush();

            $this->addFlash('success', 'User updated successfully');
            return $this->redirectToRoute('users');
        }

        return $this->render('users/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/delete/{id}', name: 'delete_user')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteOne(int $id, EntityManagerInterface $em): Response
    {
         $user = $em->getRepository(User::class)->find($id);

         if (!$user) {
            throw $this->createNotFoundException(
                "Not found"
            );
         }

         $em->remove($user);
         $em->flush();

         return $this->json([
            'message'=>'User Deleted'
         ]);
    }


}
