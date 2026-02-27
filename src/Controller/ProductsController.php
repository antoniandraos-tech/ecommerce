<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products')]
class ProductsController extends AbstractController
{
    // Afficher tous les produits (pour les clients) - ACCESSIBLE À TOUS
    #[Route('', name: 'app_products', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('user/products.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    // Page d'administration des produits - RÉSERVÉ AUX ADMINS
    #[Route('/admin', name: 'app_products_admin', methods: ['GET'])]
    public function admin(ProductRepository $productRepository): Response
    {
        // Vérifie que l'utilisateur est admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('product/admin.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    // Ajouter un nouveau produit - RÉSERVÉ AUX ADMINS
    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifie que l'utilisateur est admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Produit ajouté avec succès !');

            return $this->redirectToRoute('app_products_admin', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    // Voir un produit en détail - ACCESSIBLE À TOUS
    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    // Modifier un produit - RÉSERVÉ AUX ADMINS
    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // Vérifie que l'utilisateur est admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Produit modifié avec succès !');

            return $this->redirectToRoute('app_products_admin', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    // Supprimer un produit - RÉSERVÉ AUX ADMINS
    #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // Vérifie que l'utilisateur est admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', 'Produit supprimé avec succès !');
        }

        return $this->redirectToRoute('app_products_admin', [], Response::HTTP_SEE_OTHER);
    }
}