<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Service\MediatorS3Service;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function PHPUnit\Framework\throwException;


#[Route('/content')]
class ContentController extends AbstractController
{
    #[Route('/form', name: 'app_content_form')]
    public function form(): Response
    {
        return $this->render('content/form.html.twig');
    }

    #[Route('/send/{id}', name: 'app_content_send', methods: ['POST'])]
    public function upload(Profile $profile, Request $request, MediatorS3Service $mediatorS3Service): Response
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (!$file) {
            $this->addFlash('error', 'Nici un fisier adaugat!');
            return $this->redirectToRoute('app_upload_form');
        }

        if ($this->getUser() === null) {
            throwException('Nu exista user logat!');
        }

        $url = $mediatorS3Service->upload(
            $profile->getId(),
            $file
        );

        return $this->render('content/success.html.twig', [
            'url' => $url,
        ]);
    }

    #[Route('/fetch', name: 'app_content_fetch')]
    public function fetch(MediatorS3Service $mediatorS3Service): Response
    {

        if ($this->getUser() === null) {
            throwException('Nu exista user logat!');
        }

        $mediatorS3Service->fetchContentUrls($this->getUser()->getId() . '/');
        return $this->render('content/form.html.twig');
    }
}
