<?php


namespace App\Service\ldap;


use App\dataType\LdapType;
use App\Entity\LdapUserProperties;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Ldap\Ldap;
use function GuzzleHttp\Promise\all;

class LdapUserService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * This function retrieves the user
     * @param Entry $entry
     * @param string $userNameAttribute
     * @param $mapper
     * @return User|object
     */
    public function retrieveUserfromDatabasefromUserNameAttribute(Entry $entry,LdapType $ldapType)
    {
        $uid = $entry->getAttribute($ldapType->getUserNameAttribute())[0];
        $email = $entry->getAttribute($ldapType->getMapper()['email'])[0];
        $firstName = $entry->getAttribute($ldapType->getMapper()['firstName'])[0];
        $lastName = $entry->getAttribute($ldapType->getMapper()['lastName'])[0];

        $user = $this->em->getRepository(User::class)->findUsersfromLdapdn($entry->getDn());

        if (!$user) {
            $user = new User();
            $user->setCreatedAt(new \DateTime());
            $user->setUid(md5(uniqid()));
            $user->setUuid(md5(uniqid()));
        }
        if(!$user->getLdapUserProperties()){
            $ldap = new LdapUserProperties();
            $ldap->setLdapHost($ldapType->getUrl());
            $ldap->setLdapDn($entry->getDn());
            $user->setLdapUserProperties($ldap);
            $user->getLdapUserProperties()->setLdapNumber($ldapType->getSerVerId());
        }

        if ($ldapType->getRdn()) {
            $user->getLdapUserProperties()->setRdn($ldapType->getRdn().'='.$entry->getAttribute($ldapType->getRdn())[0]);
        }
        $specialField = array();
        foreach ($ldapType->getSpecialFields() as $data){
            if($entry->getAttribute($data)){
                $specialField[$data] = $entry->getAttribute($data)[0];
            }else{
                $specialField[$data] = '';
            }

        }
        $user->setSpezialProperties($specialField);

        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setUsername($uid);
        $this->em->persist($user);
        $this->em->flush();
        return $user;
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
                if ($data !== $data2){
                    $data->addAddressbook($data2);
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
    public function syncDeletedUser(Ldap $ldap, LdapType $ldapType)
    {
        $user = $this->em->getRepository(User::class)->findUsersByLdapServerId($ldapType->getSerVerId());
        foreach ($user as $data) {
            $this->checkUserInLdap($data, $ldap);
        }
        $user = $this->em->getRepository(User::class)->findUsersByLdapServerId($ldapType->getSerVerId());
        return $user;
    }

    /**
     * Search for the USer in LDAP
     * @param User $user
     * @param Ldap $ldap
     */
    public function checkUserInLdap(User $user, Ldap $ldap): ?Entry
    {
        $object = null;
        try {
            if($user->getLdapUserProperties()){
                $query = $ldap->query($user->getLdapUserProperties()->getLdapDn(), '(&(cn=*))');
                $object = $query->execute();
            }else{
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
        foreach ($user->getRoomModerator() as $r) {
            $user->removeRoomModerator($r);
        }
        foreach ($user->getNotifications() as $data){
            $user->removeNotification($data);
            $this->em->remove($data);
        }
        foreach ($user->getServers() as $server){
            $user->removeServer($server);
        }
        foreach ($user->getServerAdmins() as $server){
            foreach ($server->getUser() as $serverUser){
                $serverUser->removeServer($server);
            }
            $user->removeServerAdmin($server);
        }
        foreach ($user->getRoomsAttributes() as $attribute){
            $user->removeRoomsAttributes($attribute);
            $this->em->remove($attribute);
        }
        $this->em->persist($user);
        $this->em->flush();
        $this->em->remove($user);
        $this->em->flush();
    }
}