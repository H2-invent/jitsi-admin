<?php

namespace App\Service\adressbookFavoriteService;

use App\Entity\User;
use App\Exceptions\UserAlreadyAdressbookFavoriteException;
use App\Exceptions\UserNotInAdressbookException;
use App\Service\ParticipantSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdressbookFavoriteService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private TranslatorInterface             $translator,
        private LoggerInterface                 $logger,
        private ParticipantSearchService        $participantSearchService
    )
    {
    }

    /**
     * @param User $addUser
     * @param User $favoriteUser
     * @return bool
     * @throws UserNotInAdressbookException
     */
    public function addFavorite(User $addUser, User $favoriteUser): bool
    {
        if ($addUser->getAdressbookFavorites()->contains($favoriteUser)) {
            throw new UserAlreadyAdressbookFavoriteException($favoriteUser);
        }
        if (!$addUser->getAddressbook()->contains($favoriteUser)) {
            throw new UserNotInAdressbookException($favoriteUser);
        }
        $addUser->addAdressbookFavorite($favoriteUser);
        $this->entityManager->persist($addUser);
        $this->entityManager->flush();
        return true;
    }

    /**
     * @param User $addUser
     * @param User $favoriteUser
     * @return bool
     */
    public function removeFavorite(User $addUser, User $favoriteUser): bool
    {
        if (!$addUser->getAdressbookFavorites()->contains($favoriteUser)) {
            return false;
        }
        $addUser->removeAdressbookFavorite($favoriteUser);
        $this->entityManager->persist($addUser);
        $this->entityManager->flush();
        return true;
    }

    public function userFavorite(User $addUser, User $favoriteUser): array
    {
        if ($addUser->getAdressbookFavorites()->contains($favoriteUser)) {
            $this->removeFavorite($addUser, $favoriteUser);
            return ['success', $this->translator->trans('addressbook.favorite.remove.success', ['{name}' => $this->participantSearchService->buildShowInFrontendStringNoString($favoriteUser)])];
        } else {
            try {
                $this->addFavorite($addUser, $favoriteUser);
                return ['success', $this->translator->trans('addressbook.favorite.add.success', ['{name}' => $this->participantSearchService->buildShowInFrontendStringNoString($favoriteUser)])];
            } catch (UserAlreadyAdressbookFavoriteException $exception) {
                $this->logger->debug($exception->getMessage());
                return ['danger', $this->translator->trans('addressbook.favorite.add.failure')];
            } catch (UserNotInAdressbookException $exception) {
                $this->logger->debug($exception->getMessage());
                return ['danger', $this->translator->trans('addressbook.favorite.add.failure')];
            } catch (\Exception $exception) {
                $this->logger->debug($exception->getMessage());
                return ['danger', $this->translator->trans('addressbook.favorite.add.failure')];
            }
        }
    }
}
