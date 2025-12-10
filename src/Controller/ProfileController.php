<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Form\ProfileType;
use App\Repository\ProfileRepository;
use AsyncAws\S3\S3Client;
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
    public function edit(Request $request, Profile $profile, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profile/edit.html.twig', [
            'profile' => $profile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_profile_show', methods: ['GET'])]
    public function show(Profile $profile, S3Client $s3): Response
    {
        $bucket = 'amzn-s3-rmb-dev';
        $prefix = $profile->getId() . '/';
        $region = 'eu-central-1';

        // List all objects inside the prefix
        $result = $s3->listObjectsV2([
            'Bucket' => $bucket,
            'Prefix' => $prefix,
        ]);

        $images = [];

        foreach ($result->getContents() as $object) {
            $key = $object->getKey();

            if ($key === $prefix) {
                continue;
            }

            $images[] = sprintf(
                'https://%s.s3.%s.amazonaws.com/%s',
                $bucket,
                $region,
                $key
            );
        }

        return $this->render('profile/show.html.twig', [
            'profile' => $profile,
            'images' => $images,
        ]);
    }
}
