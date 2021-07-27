<?php


namespace App\Service\ldap;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Ldap\Entry;

class LdapUserService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function retrieveUserfromDatabase(Entry $entry, string $userNameAttribute, $mapper)
    {
        $uid = $entry->getAttribute($userNameAttribute);
        $email = $entry->getAttribute($mapper['email'])[0];
        $firstName = $entry->getAttribute($mapper['firstName'])[0];
        $lastName = $entry->getAttribute($mapper['lastName'])[0];

        $user = $this->em->getRepository(User::class)->findOneBy(array('username' => $uid));

        if (!$user) {
            $user = new User();
            $user->setUsername($uid);
            $user->setCreatedAt(new \DateTime());
            $user->setUid(md5(uniqid()));
            $user->setUuid(md5(uniqid()));
        }
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }
    public function connectUserwithAllUSersInAdressbock(){
        $allUSer = $this->em->getRepository(User::class)->findAll();
        foreach ($allUSer as $data){
            foreach ($allUSer as $data2){
                $data->addAddressbook($data2);

            }
            $this->em->persist($data);
        }
        $this->em->flush();
    }
}