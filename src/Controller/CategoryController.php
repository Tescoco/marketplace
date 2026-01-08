<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
    #[Route('/categories', name: 'category_list')]
    public function list(CategoryRepository $categoryRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $query = $categoryRepository->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->getQuery();

        $categories = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('category/list.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/categories/new', name: 'category_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'Category created successfully!');
            return $this->redirectToRoute('category_list');
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/categories/edit/{id}', name: 'category_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $category = $em->getRepository(Category::class)->find($id);

        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Category updated successfully!');
            return $this->redirectToRoute('category_list');
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[Route('/categories/delete/{id}', name: 'category_delete', methods: ['POST', 'GET'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $category = $em->getRepository(Category::class)->find($id);

        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        // Check if category has associated ads
        if ($category->getAds()->count() > 0) {
            $this->addFlash('danger', 'Cannot delete category with existing advertisements. Please reassign or delete the ads first.');
            return $this->redirectToRoute('category_list');
        }

        $em->remove($category);
        $em->flush();

        $this->addFlash('success', 'Category deleted successfully!');
        return $this->redirectToRoute('category_list');
    }

    #[Route('/categories/view/{id}', name: 'category_view')]
    public function view(int $id, EntityManagerInterface $em, Request $request, PaginatorInterface $paginator): Response
    {
        $category = $em->getRepository(Category::class)->find($id);

        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        $query = $em->getRepository(\App\Entity\Ads::class)
            ->createQueryBuilder('a')
            ->where('a.category = :category')
            ->setParameter('category', $category)
            ->orderBy('a.id', 'DESC')
            ->getQuery();

        $ads = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('category/view.html.twig', [
            'category' => $category,
            'ads' => $ads,
        ]);
    }
}

