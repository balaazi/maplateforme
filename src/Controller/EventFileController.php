<?php
// src/Controller/EventFileController.php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventFile;
use App\Form\EventFileType;
use App\Service\GoogleDriveService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventFileController extends AbstractController
{
#[Route('/event/{id}/files', name: 'event_files')]
public function index(Event $event, Request $request, GoogleDriveService $googleDriveService, EntityManagerInterface $em): Response
{
$form = $this->createForm(EventFileType::class);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
/** @var UploadedFile $uploadedFile */
$uploadedFile = $form['file']->getData();
$driveService = $googleDriveService->getDriveService();

$fileMetadata = new \Google\Service\Drive\DriveFile([
'name' => $uploadedFile->getClientOriginalName()
]);

$content = file_get_contents($uploadedFile->getPathname());
$file = $driveService->files->create($fileMetadata, [
'data' => $content,
'uploadType' => 'multipart',
'fields' => 'id'
]);

$eventFile = new EventFile();
$eventFile->setName($uploadedFile->getClientOriginalName());
$eventFile->setGoogleDriveFileId($file->id);
$eventFile->setEvent($event);

$em->persist($eventFile);
$em->flush();

$this->addFlash('success', 'Fichier uploadé avec succès sur Google Drive.');
return $this->redirectToRoute('event_files', ['id' => $event->getId()]);
}

return $this->render('event/files.html.twig', [
'event' => $event,
'files' => $event->getFiles(),
'form' => $form->createView(),
]);
}

#[Route('/event/{eventId}/file/{id}/delete', name: 'event_file_delete')]
public function delete(int $eventId, EventFile $file, GoogleDriveService $googleDriveService, EntityManagerInterface $em): Response
{
$driveService = $googleDriveService->getDriveService();
$driveService->files->delete($file->getGoogleDriveFileId());

$em->remove($file);
$em->flush();

$this->addFlash('success', 'Fichier supprimé avec succès.');

return $this->redirectToRoute('event_files', ['id' => $eventId]);
}
}
