<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShopController extends AbstractController
{
    // Afficher le panier
    #[Route('/basket', name: 'app_basket')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        
        $cartWithData = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                $cartWithData[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->getPrice() * $quantity
                ];
                $total += $product->getPrice() * $quantity;
            }
        }

        return $this->render('user/basket.html.twig', [
            'items' => $cartWithData,
            'total' => $total
        ]);
    }

    // Ajouter un produit au panier
    #[Route('/basket/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add($id, Request $request, ProductRepository $productRepository): Response 
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);

        $quantity = (int) $request->request->get('quantity', 1);

        $product = $productRepository->find($id);
        if (!$product) {
            $this->addFlash('error', 'Produit introuvable.');
            return $this->redirectToRoute('app_products');
        }

        if (!empty($cart[$id])) {
            $cart[$id] += $quantity;
        } else {
            $cart[$id] = $quantity;
        }

        $session->set('cart', $cart);
        
        $this->addFlash('success', 'Produit ajouté au panier !');

        return $this->redirectToRoute('app_products');
    }

    // Modifier la quantité d'un produit dans le panier
    #[Route('/basket/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update($id, Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        
        $quantity = (int) $request->request->get('quantity', 1);
        
        if ($quantity > 0 && isset($cart[$id])) {
            $cart[$id] = $quantity;
            $this->addFlash('success', 'Quantité mise à jour !');
        } elseif ($quantity <= 0) {
            unset($cart[$id]);
            $this->addFlash('success', 'Produit retiré du panier !');
        }
        
        $session->set('cart', $cart);
        
        return $this->redirectToRoute('app_basket');
    }

    // Supprimer un produit du panier
    #[Route('/basket/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove($id, Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);

        if (!empty($cart[$id])) {
            unset($cart[$id]);
            $this->addFlash('success', 'Produit retiré du panier !');
        }

        $session->set('cart', $cart);
        return $this->redirectToRoute('app_basket');
    }

    // Vider le panier
    #[Route('/basket/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('cart');
        
        $this->addFlash('success', 'Panier vidé !');
        
        return $this->redirectToRoute('app_basket');
    }
    
    #[Route('/basket/cvg', name: 'app_cvg')]
    public function cvg(Request $request): Response
    {
        if (!$this->getCvg()) {
            $this->addFlash('warning', 'Vous devez acceptez les conditions générales de ventes.');
            return $this->redirectToRoute('app_payment');
        }
    }

    // Page de paiement
    #[Route('/basket/payment', name: 'app_payment')]
    public function payment(Request $request, ProductRepository $productRepository): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Vous devez être connecté pour passer commande.');
            return $this->redirectToRoute('app_login');
        }

        $session = $request->getSession();
        $cart = $session->get('cart', []);
        
        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_products');
        }

        $cartWithData = [];
        $total = 0;
        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                $cartWithData[] = [
                    'product' => $product, 
                    'quantity' => $quantity,
                    'subtotal' => $product->getPrice() * $quantity
                ];
                $total += $product->getPrice() * $quantity;
            }
        }

        return $this->render('user/payment.html.twig', [
            'items' => $cartWithData,
            'total' => $total,
            'user' => $this->getUser()
        ]);
    }

    // Confirmer le paiement et créer la commande
    #[Route('/basket/payment/confirm', name: 'app_payment_confirm', methods: ['POST'])]
    public function paymentConfirm(
        Request $request,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $paymentMethod = $request->request->get('payment_method');
        $carrier = $request->request->get('carrier');

        if (!in_array($paymentMethod, ['card', 'paypal', 'apple_pay'])) {
            $this->addFlash('error', 'Méthode de paiement invalide.');
            return $this->redirectToRoute('app_payment');
        }

        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_products');
        }

        // Créer la commande
        $order = new Order();
        $order->setUser($this->getUser());
        $order->setStatus('payée');
        $order->setCarrier($carrier);

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                $item = new OrderItem();
                $item->setProduct($product);
                $item->setQuantity($quantity);
                $order->addItem($item);
                $entityManager->persist($item);
            }
        }

        $entityManager->persist($order);
        $entityManager->flush();

        // Vider le panier
        $session->remove('cart');
        
        $this->addFlash('success', 'Paiement effectué avec succès ! Merci pour votre commande.');

        return $this->render('user/payment_confirm.html.twig', [
            'payment_method' => $paymentMethod,
            'order' => $order
        ]);
    }
}