<?php

namespace App\Service\ldap;

use App\dataType\LdapType;
use App\Entity\LdapUserProperties;
use App\Entity\User;
use App\Service\IndexUserService;
use App\Service\UserCreatorService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Ldap\Ldap;

class LdapUserService
{
    private $em;
    private $userCreationService;
    private $indexer;
    private $logger;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, UserCreatorService $userCreationService, IndexUserService $indexUserService)
    {
        $this->em = $entityManager;
        $this->userCreationService = $userCreationService;
        $this->indexer = $indexUserService;
        $this->logger = $logger;
    }

    /**
     * This function retrieves the user
     * @param Entry $entry
     * @param string $userNameAttribute
     * @param $mapper
     * @return User|object
     */
    public function retrieveUserfromDatabasefromUserNameAttribute(Entry $entry, LdapType $ldapType, $dryRun = false): ?User
    {
        //Here we get the attributes from the LDAP (username, email, firstname, lastname)
        try {
            $uid = $entry->getAttribute($ldapType->getUserNameAttribute())[0];
            $email = $entry->getAttribute($ldapType->getMapper()['email'])[0] ?? '';
            $firstName = $entry->getAttribute($ldapType->getMapper()['firstName'])[0] ?? null;
            $lastName = $entry->getAttribute($ldapType->getMapper()['lastName'])[0] ?? null;
            $user = $this->em->getRepository(User::class)->findUsersfromLdapdn($entry->getDn());
            if (!$user) {
                $user = $this->em->getRepository(User::class)->findOneBy(['username' => $uid]);
            }
            if (!$user) {
                $user = $this->userCreationService->createUser($email, $uid, $firstName, $lastName, $dryRun);
                $user->setUid(md5(uniqid()));
            }
            if (!$user->getLdapUserProperties()) {
                $ldap = new LdapUserProperties();
                $ldap->setLdapHost($ldapType->getUrl());
                $ldap->setLdapDn($entry->getDn());
                $user->setLdapUserProperties($ldap);
                $user->getLdapUserProperties()->setLdapNumber($ldapType->getSerVerId());
            }

            if ($ldapType->getRdn()) {
                $user->getLdapUserProperties()->setRdn($ldapType->getRdn() . '=' . $entry->getAttribute($ldapType->getRdn())[0]);
            }
            $specialField = $this->getSpezialPropertiesFields(ldapType: $ldapType, entry: $entry);

            $user->setSpezialProperties($specialField);

            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setUsername($uid);

            $user->setIndexer($this->indexer->indexUser($user));
            if (!$dryRun) {
                $this->em->persist($user);
                $this->em->flush();
            }
            return $user;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), $exception->getFile() . 'Line: ' . $exception->getLine());
        }
        return null;
    }

    /**
     * This function connects all users in the Database with the adressbook of all available other users.
     * So everyon can search for everyone in the whole jitsi-admin system
     *
     */
    public function connectUserwithAllUSersInAdressbock()
    {
        $allUSer = $this->em->getRepository(User::class)->findUsersfromLdapService();
        foreach ($allUSer as $data) {
            foreach ($allUSer as $data2) {
                $data->addAddressbook($data2);
            }
            $this->em->persist($data);
        }
        $this->em->flush();
        return $allUSer;
    }

    /**
     *This Function removes the own user from the adressbook
     */
    public function cleanUpAdressbook()
    {
        $allUSer = $this->em->getRepository(User::class)->findUsersfromLdapService();
        foreach ($allUSer as $data) {
            foreach ($allUSer as $data2) {
                if ($data === $data2) {
                    if (in_array($data2, $data->getAddressbook()->toArray())) {
                        $data->removeAddressbook($data2);
                    }
                }
            }
            $this->em->persist($data);
        }
        $this->em->flush();
        return $allUSer;
    }

    /**
     * returns all valid users from the database which are in the ldap and the Database
     * @param Ldap $ldap
     * @param $ldapServerId
     */
    public function syncDeletedUser(LdapType $ldapType)
    {
        $user = $this->em->getRepository(User::class)->findUsersByLdapServerId($ldapType->getSerVerId());
        foreach ($user as $data) {
            $this->checkUserInLdap($data, $ldapType);
        }
        $user = $this->em->getRepository(User::class)->findUsersByLdapServerId($ldapType->getSerVerId());
        return $user;
    }

    /**
     * Search for the USer in LDAP
     * @param User $user
     * @param Ldap $ldap
     */
    public function checkUserInLdap(User $user, LdapType $ldap): ?Entry
    {
        $object = null;
        $filterString = $ldap->buildObjectClass();

        try {
            if ($user->getLdapUserProperties()) {
                $query = $ldap->getLdap()->query($user->getLdapUserProperties()->getLdapDn(), $filterString);
                $object = $query->execute();
            } else {
                return null;
            }
            if (sizeof($object->toArray()) === 0) {
                $this->deleteUser($user);
                return null;
            }
        } catch (LdapException $e) {
            $this->deleteUser($user);
            return null;
        }
        return $object->toArray()[0];
    }

    /**
     * Delete User and remove all Addressbooks entrys
     * @param User $user
     */
    public function deleteUser(User $user)
    {
        foreach ($user->getAddressbookInverse() as $u) {
            $u->removeAddressbook($user);
            $this->em->persist($u);
        }
        foreach ($user->getRooms() as $r) {
            $user->removeRoom($r);
        }
        $rooms = $user->getRoomModerator();
        foreach ($rooms as $r) {
            foreach ($r->getUser() as $u) {
                $r->removeUser($u);
            }
            $this->em->persist($r);
        }

        foreach ($user->getRoomModerator() as $r) {
            $user->removeRoomModerator($r);
        }
        foreach ($user->getCreatorOf() as $r) {
            $user->removeCreatorOf($r);
        }
        foreach ($user->getNotifications() as $data) {
            $user->removeNotification($data);
            $this->em->remove($data);
        }
        foreach ($user->getServers() as $server) {
            $user->removeServer($server);
        }
        foreach ($user->getServerAdmins() as $server) {
            foreach ($server->getUser() as $serverUser) {
                $serverUser->removeServer($server);
            }
            $user->removeServerAdmin($server);
        }
        foreach ($user->getRoomsAttributes() as $attribute) {
            $user->removeRoomsAttributes($attribute);
            $this->em->remove($attribute);
        }
        foreach ($user->getLobbyWaitungUsers() as $data) {
            $user->removeLobbyWaitungUser($data);
            $this->em->remove($data);
        }
        if ($user->getLdapUserProperties()) {
            $this->em->remove($user->getLdapUserProperties());
        }
        foreach ($user->getManagerElement() as $depElement) {
            $this->em->remove($depElement);
        }

        foreach ($user->getDeputiesElement() as $depElement) {
            $this->em->remove($depElement);
        }
        foreach ($user->getLogs() as $logElement) {
            $user->removeLog($logElement);
            $logElement->setUser($logElement->getRoom()->getModerator());
           $this->em->persist($logElement);
        }

        $this->em->persist($user);
        $this->em->flush();
        $this->em->remove($user);
        $this->em->flush();
    }

    public function getSpezialPropertiesFields(LdapType $ldapType, Entry $entry)
    {
        $specialField = [];
        foreach ($ldapType->getSpecialFields() as $key => $data) {
            if ($entry->getAttribute($data)) {
                $specialField[$key] = $entry->getAttribute($data)[0];
            } else {
                $specialField[$key] = '';
            }
        }
        return $specialField;
    }
}
