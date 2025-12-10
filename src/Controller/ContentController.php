<?php

namespace App\Controller;

use App\Service\S3UploaderService;
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

    #[Route('/send', name: 'app_content_send', methods: ['POST'])]
    public function upload(Request $request, S3UploaderService $s3UploaderService): Response
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

        $url = $s3UploaderService->upload(
            $this->getUser(),
            $file
        );

        return $this->render('content/success.html.twig', [
            'url' => $url,
        ]);
    }

    #[Route('/fetch', name: 'app_content_fetch')]
    public function fetch(): Response
    {
        return $this->render('content/form.html.twig');
    }
}
