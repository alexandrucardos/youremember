<?php

namespace App\Controller;

use App\Service\S3Uploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UploadController extends AbstractController
{
    #[Route('/upload', name: 'upload_form')]
    public function form(): Response
    {
        return $this->render('upload/form.html.twig');
    }

    #[Route('/upload/send', name: 'upload_send', methods: ['POST'])]
    public function upload(Request $request, S3Uploader $uploader): Response
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (!$file) {
            $this->addFlash('error', 'No file uploaded.');
            return $this->redirectToRoute('upload_form');
        }

        $key = 'uploads/' . $file->getClientOriginalName();

        $url = $uploader->upload(
            $key,
            file_get_contents($file->getPathname()),
            $file->getMimeType()
        );

        return $this->render('upload/success.html.twig', [
            'url' => $url,
        ]);
    }
}
