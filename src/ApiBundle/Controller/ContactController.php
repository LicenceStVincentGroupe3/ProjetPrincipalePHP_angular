<?php

namespace App\ApiBundle\Controller;

use App\AdminBundle\Entity\Contact;
use App\AdminBundle\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/contact")
 */
class ContactController extends AbstractController
{
    /**
     * @Route("/", methods={"POST"})
     */
    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder, SerializerInterface $serializer)
    {
                $contact = new Contact();
                $display = $this->getDoctrine()->getManager();
                $form = $this->createForm(ContactType::class, $contact);
                $repository = $display->getRepository(Contact::class);
                $listContact = $repository->findAll();

                // La méthode handleRequest de la class form permet de récupérer les valeurs des champs dans les imputs du formulaire //
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid() && $request->isMethod('POST')) {
                    $contact = $form->getData();
                    $contactPhoto = $form['contactPhoto']->getData();
                    $contactLastName = $form['contactLastName']->getData();
                    $contactFirstName = $form['contactFirstName']->getData();

                $file = $contactPhoto;

                if ($file !== null)
                {
                    $fileName = $file->getClientOriginalName();
                    $newFileName = $contactLastName.'_'.$contactFirstName.'_'.$fileName;

                    // On envoit le fichier dans le dossier images
                    try {
                        $file->move($this->getParameter('images_directory'), $newFileName);
                    } catch (FileException $e) {
                        // S'il y a un soucis pendant l'upload on catch
                    }

                    $contact->setContactPhoto($newFileName);
                }

                $entityManager = $this->getDoctrine()->getManager();
                // Persist prépare l'entité "contact" pour la création //
                $entityManager->persist($contact);
                // Flush envoie les infos en base (ajout) //
                $entityManager->flush();
                return $this->redirect($this->generateUrl('listCont'));
                //return $this->render('contact/new.html.twig', array('form' => $form->createView(),));
                }
                return $this->render('contact/new.html.twig', array('form' => $form->createView(), 'lesContacts' => $listContact));
    }

    /**
     * @Route("/edit/id", requirements={"id"="\d+"}, methods={"POST"})
     */
    public function edit($id, Request $request)
    {
                // Appel de Doctrine
                $repository = $this->getDoctrine()->getManager()->getRepository(Contact::class);
                $editContact = $repository->find($id);

                $display = $this->getDoctrine()->getManager();

                $repository = $display->getRepository(Contact::class);
                $listContact = $repository->findAll();

                // Equivalent du SELECT * where id=(paramètre) //
                $form = $this->createForm(ContactType::class, $editContact);
                $form->add('contactStatus', CheckboxType::class, array(
                    'label'    => 'Statut Actif',
                    'required' => false));

                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid() && $request->isMethod('POST')) {
                    $editContact = $form->getData();
                    $contactPhoto = $form['contactPhoto']->getData();
                    $contactLastName = $form['contactLastName']->getData();
                    $contactFirstName = $form['contactFirstName']->getData();

                    $file = $contactPhoto;

                    $entityManager = $this->getDoctrine()->getManager();

                    if ($file !== null)
                    {
                        // On vérifie si le fichier est en base
                        if($editContact->getContactPhoto() !== null)
                        {
                            // Variable qui contient l'ancien fichier
                            $oldFile = $this->getParameter('images_directory').'/'.
                                $editContact->getContactPhoto();

                            // On supprime l'ancien fichier en local
                            if (file_exists($oldFile)) {
                                unlink($oldFile);
                            }
                        }

                        $fileName = $file->getClientOriginalName();
                        $newFileName = $contactLastName.'_'.$contactFirstName.'_'.$fileName;

                        // On envoit le fichier dans le dossier images
                        try {
                            $file->move($this->getParameter('images_directory'), $newFileName);
                        } catch (FileException $e) {
                            // S'il y a un soucis pendant l'upload on catch
                        }

                        $editContact->setContactPhoto($newFileName);
                    }
                    $editContact->setContactDateUpdatePlug(new \DateTime());
                    $entityManager->flush();

                    return $this->redirect($this->generateUrl('listCont'));
                }
                return $this->render('contact/edit.html.twig', array('form' => $form->createView(), 'lesContacts' => $listContact, 'editContact' => $editContact ));
    }


    /**
     * @Route("/", methods={"GET"})
     */
    public function list(Request $request, SerializerInterface $serializer)
    {
        // Appel de Doctrine
        $display = $this->getDoctrine()->getManager();

        // Variable qui contient le Repository
        $contactRepository = $display->getRepository(Contact::class);

        // Equivalent du SELECT *
        $nbContact = $contactRepository->countContactActive();


        $response = new JsonResponse(["contactActive" => $nbContact[1]]);

        header("Access-Control-Allow-Origin: *");
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
