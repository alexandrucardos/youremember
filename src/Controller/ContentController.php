<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Service\MediatorS3Service;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @deprecated
 */
#[Route('/content')]
class ContentController extends AbstractController
{
    #[Route('/form', name: 'app_content_form')]
    public function form(): Response
    {
        return $this->render('pictures.profile.background.form.html.twig');
    }

    #[Route('/send/{id}/type/{type}', name: 'app_content_profileImage_send', methods: ['POST'])]
    public function uploadProfileImage(
        Profile           $profile,
        string            $type,
        Request           $request,
        MediatorS3Service $mediatorS3Service
    ): Response
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (!$file) {
            $this->addFlash('error', 'Nici un fisier adaugat!');
            return $this->redirectToRoute('app_profile_edit', ['id' => $profile->getId()]);
        }

        if ($this->getUser() === null) {
            throw new \Exception('Nu exista user logat!');
        }

        $url = $mediatorS3Service->uploadSingle(
            $profile->getId(),
            $file,
            $type
        );

        return $this->render('content/success.html.twig', [
            'url' => $url,
        ]);
    }

    #[Route('/send/{id}', name: 'app_content_send', methods: ['POST'])]
    public function upload(Profile $profile, Request $request, MediatorS3Service $mediatorS3Service): Response
    {
        /** @var array<UploadedFile>|null $files */
        $files = $request->files->get('files');

        if (!$files) {
            $this->addFlash('error', 'Nici un fisier adaugat!');
            return $this->redirectToRoute('app_profile_edit', ['id' => $profile->getId()]);
        }

        if ($this->getUser() === null) {
            throw new \Exception('Nu exista user logat!');
        }

        $url = $mediatorS3Service->uploadMultiple(
            $profile->getId(),
            $files
        );

        return $this->render('content/success.html.twig', [
            'url' => $url,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_content_delete', methods: ['POST'])]
    #[IsGranted('edit', 'profile')]
    public function delete(Request $request, Profile $profile, MediatorS3Service $mediatorS3Service): Response
    {
        if ($this->getUser() === null) {
            throw new \Exception('Nu exista user logat!');
        }

        $url = $request->request->get('image');

        if (!$this->isCsrfTokenValid('delete_image_' . $url,
            $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $mediatorS3Service->deleteContent($url);

        return $this->redirectToRoute('app_profile_edit', ['id' => $profile->getId()]);
    }
}
