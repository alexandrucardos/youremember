<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Form\ProfileType;
use App\Repository\ProfileRepository;
use App\Service\Profile\ProfileFetchService;
use App\Service\Profile\ProfileViewService;
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
        ProfileViewService     $profileViewService,
    ): Response
    {
        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        $mediaData = $profileViewService->buildMediaData($profile);

        return $this->render('profile/edit.html.twig', [
            'profile' => $profile,
            'form' => $form,
            'backgroundPictureUrl' => $mediaData['backgroundPictureUrl'],
            'profilePictureUrl' => $mediaData['profilePictureUrl'],
            'images' => $mediaData['images'],
            'canDelete' => true,
        ]);
    }

    #[Route('/{id}', name: 'app_profile_show', methods: ['GET'])]
    public function show(
        Request $request,
        ProfileFetchService $profileFetchService,
        ProfileViewService $profileViewService
    ): Response
    {
        $profile = $profileFetchService->fetchById($request->attributes->get('id'));

        $mediaData = $profileViewService->buildMediaData($profile);

        return $this->render('profile/show.html.twig', [
            'profile' => $profile,
            'images' => $mediaData['images'],
            'backgroundPictureUrl' => $mediaData['backgroundPictureUrl'],
            'profilePictureUrl' => $mediaData['profilePictureUrl'],
        ]);
    }
}
