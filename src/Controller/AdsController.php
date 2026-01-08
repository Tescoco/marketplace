<?php
namespace App\Controller;

use App\Entity\Ads;
use App\Form\AdsType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Knp\Component\Pager\PaginatorInterface;

class AdsController extends AbstractController 
{
    #[Route('/', name: 'home')]
    public function list(EntityManagerInterface $em, Request $request, PaginatorInterface $paginator): Response 
    {
        $user = $this->getUser();

        $query = $em->getRepository(Ads::class)
        ->createQueryBuilder('a')
        ->orderBy('a.id', 'DESC')
        ->getQuery();

        $ads = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // items per page
        );

        return $this->render('ads/list.html.twig', [
            'ads' => $ads,
            'currentUser' => $user
        ]);
    }

 

    #[Route('/ads/new', name: 'new_ads')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $ads = new Ads();

        $form = $this->createForm(AdsType::class, $ads);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user) {
                $ads->setUser($user);
            }

            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/ads',
                        $newFilename
                    );
                    $ads->setImageUrl($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Failed to upload image. Please try again.');
                }
            }

            $em->persist($ads);
            $em->flush();

            $this->addFlash('success', 'Advertisement created successfully!');
            return $this->redirectToRoute('my_ads');
        }

        return $this->render('ads/new.html.twig',[
            'form' => $form->createView()
        ]);
    }

    #[Route('/ads/edit/{id}', name: 'edit_ad')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(int $id, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $ad = $em->getRepository(Ads::class)->find($id);

        if (!$ad) {
           throw $this->createNotFoundException("Not found $id");
        }

        // Check if user owns the ad
        $user = $this->getUser();
        if (!$user || $ad->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You do not have permission to edit this ad.');
        }

        $form = $this->createForm(AdsType::class, $ad);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Delete old image if exists
                if ($ad->getImageUrl()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir').'/public/uploads/ads/'.$ad->getImageUrl();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/ads',
                        $newFilename
                    );
                    $ad->setImageUrl($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Failed to upload image. Please try again.');
                }
            }

            $em->flush();

            $this->addFlash('success', 'Advertisement updated successfully!');
            return $this->redirectToRoute('view_ad', ['id' => $ad->getId()]);
        }

        return $this->render('ads/edit.html.twig', [
            'form' => $form->createView(),
            'ad' => $ad
        ]);
    }

    #[Route('/ads', name: 'my_ads')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function myAds(EntityManagerInterface $em, Request $request, PaginatorInterface $paginator): Response
    {
       $user = $this->getUser();

       $query = $em->getRepository(Ads::class)
    ->createQueryBuilder('a')
    ->where('a.user = :user') 
    ->setParameter('user', $user)
    ->orderBy('a.id', 'DESC')
    ->getQuery();


       $ads = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        10 // items per page
    );

        return $this->render('ads/list.html.twig', [
            'ads' => $ads,
            'currentUser' => $user
        ]);
    }

    #[Route('/ads/delete/{id}', name: 'delete_ad')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteOne(int $id, Request $request, EntityManagerInterface $em): Response 
    {
         $ad = $em->getRepository(Ads::class)->find($id);

         if (!$ad) {
            throw $this->createNotFoundException("Advertisement not found");
         }

         // Check if user owns the ad or is moderator
         $user = $this->getUser();
         if (!$this->isGranted('ROLE_MODERATOR') && (!$user || $ad->getUser()->getId() !== $user->getId())) {
             throw $this->createAccessDeniedException('You do not have permission to delete this ad.');
         }

         // Delete image file if exists
         if ($ad->getImageUrl()) {
             $imagePath = $this->getParameter('kernel.project_dir').'/public/uploads/ads/'.$ad->getImageUrl();
             if (file_exists($imagePath)) {
                 unlink($imagePath);
             }
         }

         $em->remove($ad);
         $em->flush();

         $this->addFlash('success', 'Advertisement deleted successfully!');
         return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('home'));
    }

    #[Route('/ads/{id}', name: 'view_ad')]
    public function view(int $id, EntityManagerInterface $em): Response
    {
       $user = $this->getUser();
       $ad = $em->getRepository(Ads::class)->find($id);

       if (!$ad) {
        throw $this->createNotFoundException("Not found $id");
       }

        return $this->render('ads/view.html.twig', [
            'ad' => $ad,
            'currentUser' => $user
        ]);
    }

   


}
