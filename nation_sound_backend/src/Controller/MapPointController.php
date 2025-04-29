<?php

namespace App\Controller;

use App\Entity\MapPoint;
use App\Form\MapPointForm;
use App\Repository\MapPointRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/map/point')]
#[IsGranted("ROLE_ADMIN")]
final class MapPointController extends AbstractController
{
    #[Route(name: 'app_map_point_index', methods: ['GET'])]
    public function index(MapPointRepository $mapPointRepository): Response
    {
        return $this->render('map_point/index.html.twig', [
            'map_points' => $mapPointRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_map_point_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $mapPoint = new MapPoint();
        $form = $this->createForm(MapPointForm::class, $mapPoint);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($mapPoint);
            $entityManager->flush();

            return $this->redirectToRoute('app_map_point_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('map_point/new.html.twig', [
            'map_point' => $mapPoint,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_map_point_show', methods: ['GET'])]
    public function show(MapPoint $mapPoint): Response
    {
        return $this->render('map_point/show.html.twig', [
            'map_point' => $mapPoint,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_map_point_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MapPoint $mapPoint, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MapPointForm::class, $mapPoint);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_map_point_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('map_point/edit.html.twig', [
            'map_point' => $mapPoint,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_map_point_delete', methods: ['POST'])]
    public function delete(Request $request, MapPoint $mapPoint, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$mapPoint->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($mapPoint);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_map_point_index', [], Response::HTTP_SEE_OTHER);
    }
}
