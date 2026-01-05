<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Form\ProfileType;
use App\Repository\ProfileRepository;
use App\Service\MediatorS3Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
final class ProfileController extends AbstractController
{
    #[Route(name: 'app_profile_index', methods: ['GET'])]
    public function index(ProfileRepository $profileRepository): Response
    {
        return $this->render('profile/index.html.twig', [
            'profiles' => $profileRepository->findByUserId($this->getUser()),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted('edit', 'profile')]
    public function edit(
        Request                $request,
        Profile                $profile,
        EntityManagerInterface $entityManager,
        MediatorS3Service      $mediatorS3Service
    ): Response
    {
        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        $profileId = $profile->getId();

        $backgroundPictureUrl = $mediatorS3Service->buildUrl(
            sprintf(
                '%d/%s/%s',
                $profileId,
                MediatorS3Service::FOLDER_PROFILE,
                MediatorS3Service::PROFILE_BACKGROUND
            )
        );

        $profilePictureUrl = $mediatorS3Service->buildUrl(
            sprintf(
                '%d/%s/%s',
                $profileId,
                MediatorS3Service::FOLDER_PROFILE,
                MediatorS3Service::PROFILE_PICTURE
            )
        );

        $picturesUrls = $mediatorS3Service->fetchContentUrls(
            sprintf(
                '%d/%s',
                $profileId,
                MediatorS3Service::FOLDER_IMAGES,
            )
        );

        return $this->render('profile/edit.html.twig', [
            'profile' => $profile,
            'form' => $form,
            'backgroundPictureUrl' => $backgroundPictureUrl,
            'profilePictureUrl' => $profilePictureUrl,
            'images' => $picturesUrls,
            'canDelete' => true,
        ]);
    }

    #[Route('/{id}', name: 'app_profile_show', methods: ['GET'])]
    public function show(Profile $profile, MediatorS3Service $mediatorS3Service): Response
    {
        $profileId = $profile->getId();

        //todo all this fetches can be unified somehow
        $backgroundPictureUrl = $mediatorS3Service->buildUrl(
            sprintf(
                '%d/%s/%s',
                $profileId,
                MediatorS3Service::FOLDER_PROFILE,
                MediatorS3Service::PROFILE_BACKGROUND
            )
        );

        $profilePictureUrl = $mediatorS3Service->buildUrl(
            sprintf(
                '%d/%s/%s',
                $profileId,
                MediatorS3Service::FOLDER_PROFILE,
                MediatorS3Service::PROFILE_PICTURE
            )
        );

        $picturesUrls = $mediatorS3Service->fetchContentUrls(
            sprintf(
                '%d/%s',
                $profileId,
                MediatorS3Service::FOLDER_IMAGES,
            )
        );

        // todo images -> pictures to many concepts
        return $this->render('profile/show.html.twig', [
            'profile' => $profile,
            'images' => $picturesUrls,
            'backgroundPictureUrl' => $backgroundPictureUrl,
            'profilePictureUrl' => $profilePictureUrl,
        ]);
    }
}
