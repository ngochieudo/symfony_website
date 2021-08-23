<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use function PHPUnit\Framework\throwException;

/**
 * @IsGranted("ROLE_USER")
 */
class ProductController extends AbstractController
{
    /**
     * @Route("/product", name="product_index")
     */
    public function indexProduct()
    {
        $products = $this->getDoctrine()->getRepository(Product::class)->findAll();

        return $this->render(
            'product/index.html.twig',
            [
                'products' => $products
            ]
        );
    }

    /**
     * @Route("/product/detail/{id}", name="product_detail")
     */
    public function detailProduct($id)
    {
        $product = $this->getDoctrine()->getRepository(Product::class)->find($id);

        return $this->render(
            'product/detail.html.twig',
            [
                'product' => $product
            ]
        );
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/product/delete/{id}", name="product_delete")
     */
    public function deleteProduct($id)
    {
        $product = $this->getDoctrine()->getRepository(Product::class)->find($id);

        if ($product == null) {
            $this->addFlash("Error", "Failed to delete!");
            return $this->redirectToRoute("product_index");
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($product);
        $manager->flush();

        $this->addFlash("Info", "Delete successfully !");
        return $this->redirectToRoute("product_index");
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("product/create", name="product_create")
     */
    public function createProduct(Request $request)
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if(!$form['image']->getData())
            {
                $this->addFlash("Error", "Product has no image !");
                return $this->redirectToRoute("product_index");
            }
            $image = $product->getImage();
            $fileName = md5(uniqid());
            $fileExtension = $image->guessExtension();
            $imageName = $fileName . '.' . $fileExtension;

            try {
                $image->move(
                    $this->getParameter('product_image'),
                    $imageName
                );
            } catch (FileException $e) {
                throwException($e);
            }

            $product->setImage($imageName);

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($product);
            $manager->flush();

            $this->addFlash("Info", "Create new product success !");
            return $this->redirectToRoute("product_index");
        }

        return $this->render(
            'product/create.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }

    /**
     * @Route("product/update/{id}", name="product_update")
     */
    public function updateProduct(Request $request, Product $product)
    {

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadFile = $form['image']->getData();
            if ($uploadFile != null) {
                //get Image from uploaded file
                $image = $product->getImage();
                $fileName = md5(uniqid());
                $fileExtension = $image->guessExtension();
                $imageName = $fileName . '.' . $fileExtension;


                try {
                    $image->move(
                        $this->getParameter('product_image'),
                        $imageName
                    );
                } catch (FileException $e) {
                    throwException($e);
                }

                //set imageName to database
                $product->setImage($imageName);
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($product);
                $manager->flush();

                $this->addFlash("Info", "Update completed !");
                return $this->redirectToRoute("product_index");
            }
            $this->addFlash("Error", "Must add image into product !");
            return $this->redirectToRoute("product_index");
        }

        return $this->render(
            'product/update.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }
}
