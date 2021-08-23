<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Category;
use App\Form\CategoryType;
use Doctrine\DBAL\ForwardCompatibility\Result;
use function PHPUnit\Framework\throwException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER")
 */
class CategoryController extends AbstractController
{
    /**
     * @Route("/category/api/view", methods={"GET"}, name="view_all_category_api")
     */
    public function viewAllCategoryAPI(SerializerInterface $serializer)
    {

        $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();

        $data = $serializer->serialize($categories, 'json');
        return new Response(
            $data,
            Response::HTTP_OK,
            [
                "content-type" => "application/json"
            ]
        );
    }
    /**
     * @Route("/category/api/view/{id}", methods={"GET"}, name="view_category_by_id_api")
     */
    public function viewCategoryByIdAPI(SerializerInterface $serializer, $id)
    {
        $category = $this->getDoctrine()->getRepository(Category::class)->find($id);

        if ($category == null) {
            $error = "ERROR: Category ID is invalid";
            return new Response(
                $error,
                Response::HTTP_NOT_FOUND,
                [
                    "content-type" => "application/json"
                ]
            );
        }
        $json = $serializer->serialize($category, 'json');
        return new Response(
            $json,
            200,
            [
                "content-type" => "application/json"
            ]
        );
    }
    /**
     * @Route("/category/api/delete/{id}", methods={"DELETE"}, name="delete_category_by_id_api")
     */
    public function DeleteCategoryByIdAPI($id)
    {
        try {
            $category = $this->getDoctrine()->getRepository(Category::class)->find($id);
            if ($category == null) {
                $error = "ERROR: Category ID is invalid !";
                return new Response(
                    $error,
                    Response::HTTP_FOUND
                );
            } else {
                $manager = $this->getDoctrine()->getManager();
                $manager->remove($category);
                $manager->flush();
                $message = "INFO: Delete successfully !";
                return new Response(
                    $message,
                    Response::HTTP_ACCEPTED
                );
            }
        } catch (\Exception $e) {
            $error = ['ERROR' => $e->getMessage()];
            $json = json_encode($error);
            return new Response(
                $json,
                Response::HTTP_BAD_REQUEST,
                [
                    "content-type" => "application/json"
                ]
            );
        }
    }

    /**
     * @Route("/category/api/create", methods={"POST"}, name="add_category_api")
     */
    public function createCategoryAPI(Request $request)
    {
        try {
            $category = new Category();
            $data = json_decode($request->getContent(), true);
            $category->setName($data['name']);
            $category->setDescription($data['description']);
            $category->setDate(\DateTime::createFromFormat('Y-m-d', $data['date']));
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($category);
            $manager->flush();
            return new Response(
                "Add successfully !",
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            $error = ['ERROR' => $e->getMessage()];
            $json = json_encode($error);
            return new Response(
                $json,
                Response::HTTP_BAD_REQUEST,
                [
                    "content-type" => "application/json"
                ]
            );
        }
    }

    /**
     * @Route("/category/api/update/{id}", methods={"PUT"}, name="update_category_api")
     */
    public function updateCategoryAPI(Request $request, $id)
    {
        try {
            $category = $this->getDoctrine()
                ->getRepository(Category::class)
                ->find($id);
            $data = json_decode($request->getContent(), true);
            $category->setName($data['name']);
            $category->setDescription($data['description']);
            $category->setDate(\DateTime::createFromFormat('Y-m-d', $data['date']));
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($category);
            $manager->flush();
            return new Response(
                "Update successfully !",
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            $error = ['ERROR' => $e->getMessage()];
            $json = json_encode($error);
            return new Response(
                $json,
                Response::HTTP_BAD_REQUEST,
                [
                    "content-type" => "application/json"
                ]
            );
        }
    }

    /**
     * @Route ("/category", name="category_index")
     */
    public function indexCategory()
    {
        $categories = $this->getDoctrine()
            ->getRepository(Category::class)
            ->findAll();
        return $this->render(
            "category/index.html.twig",
            [
                "categories" => $categories
            ]
        );
    }
    /**
     * @Route ("/category/create", name="category_create")
     */
    public function createnewCategory(Request $request)
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($category);
            $manager->flush();
            $this->addFlash("Info", "Add successfully !");
            return $this->redirectToRoute("category_index");
        }

        return $this->render(
            "category/create.html.twig",
            [
                'form' => $form->createView()
            ]
        );
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route ("/category/delete/{id}", name="category_delete")
     */
    public function deleteCategory($id)
    {
        $category = $this->getDoctrine()
            ->getRepository(Category::class)
            ->find($id);
        if ($category == null) {
            $this->addFlash("Error", "Delete failed !");
            return $this->redirectToRoute("category_index");
        }

        $manager = $this->getDoctrine()
            ->getManager();
        $manager->remove($category);
        $manager->flush();
        $this->addFlash("Info", "Delete succeed !");
        return $this->redirectToRoute("category_index");
    }
    /**
     * @Route ("/category/detail/{id}", name="category_detail")
     */
    public function viewdetailCategory($id)
    {
        $category = $this->getDoctrine()
            ->getRepository(Category::class)
            ->find($id);
        return $this->render(
            "category/detail.html.twig",
            [
                "category" => $category
            ]
        );
    }

    /**
     * * @IsGranted("ROLE_ADMIN")
     * @Route ("/category/update/{id}", name="category_update")
     */
    public function updateCategory(Request $request, $id)
    {
        $category = $this->getDoctrine()
            ->getRepository(Category::class)
            ->find($id);
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            //set imageName to database
            $manager = $this->getDoctrine()->getManager();

            $uploadFile = $form['image']->getData();
            if ($uploadFile != null) {
                //get Image from uploaded file
                $image = $category->getImage();
                $fileName = md5(uniqid());
                $fileExtension = $image->guessExtension();
                $imageName = $fileName . '.' . $fileExtension;

                try {
                    $image->move(
                        $this->getParameter('category_image'),
                        $imageName
                    );
                } catch (FileException $e) {
                    throwException($e);
                }
                $category->setImage($imageName);
            }
            $manager->persist($category);
            $manager->flush();
            $this->addFlash("Info", "Update successfully !");
            return $this->redirectToRoute("category_index");
        }

        return $this->render(
            "category/update.html.twig",
            [
                'form' => $form->createView()
            ]
        );
    }
}
