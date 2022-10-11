<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    private $hasher;

    private $demoUsers;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager)
    {
        $this->importDemoUsers($manager);

        $manager->flush();
    }

    private function importDemoUsers(ObjectManager $manager)
    {
        $this->demoUsers[0] = new User();
        $this->demoUsers[0]->setUsername('admin');
        $this->demoUsers[0]->setRoles(['ROLE_ADMIN']);
        $this->demoUsers[0]->setPassword($this->hasher->hashPassword($this->demoUsers[0], 'start1'));
        $this->demoUsers[0]->setEmail('admin@test.local');
        $this->demoUsers[0]->setLastname('admin');
        $this->demoUsers[0]->setCreatedAt(new \DateTime());
        $this->demoUsers[0]->setCreatedBy($this->demoUsers[0]);
        $this->demoUsers[0]->setUpdatedAt(new \DateTime());
        $this->demoUsers[0]->setUpdatedBy($this->demoUsers[0]);
        $this->demoUsers[0]->setUserStatus('active');
        $this->demoUsers[0]->setForcePasswordChange(false);
        $manager->persist($this->demoUsers[0]);

        $this->demoUsers[1] = new User();
        $this->demoUsers[1]->setUsername('user1');
        $this->demoUsers[1]->setRoles(['ROLE_USER']);
        $this->demoUsers[1]->setPassword($this->hasher->hashPassword($this->demoUsers[1], 'start1'));
        $this->demoUsers[1]->setEmail('user1@test.local');
        $this->demoUsers[1]->setLastname('user1');
        $this->demoUsers[1]->setCreatedAt(new \DateTime());
        $this->demoUsers[1]->setCreatedBy($this->demoUsers[0]);
        $this->demoUsers[1]->setUpdatedAt(new \DateTime());
        $this->demoUsers[1]->setUpdatedBy($this->demoUsers[0]);
        $this->demoUsers[1]->setUserStatus('active');
        $this->demoUsers[1]->setForcePasswordChange(false);
        $manager->persist($this->demoUsers[1]);

        $this->demoUsers[2] = new User();
        $this->demoUsers[2]->setUsername('user2');
        $this->demoUsers[2]->setRoles(['ROLE_USER']); //Role for all co-workers
        $this->demoUsers[2]->setPassword($this->hasher->hashPassword($this->demoUsers[2], 'start1'));
        $this->demoUsers[2]->setEmail('user2@test.local');
        $this->demoUsers[2]->setLastname('user2');
        $this->demoUsers[2]->setCreatedAt(new \DateTime());
        $this->demoUsers[2]->setCreatedBy($this->demoUsers[0]);
        $this->demoUsers[2]->setUpdatedAt(new \DateTime());
        $this->demoUsers[2]->setUpdatedBy($this->demoUsers[0]);
        $this->demoUsers[2]->setUserStatus('active');
        $this->demoUsers[2]->setForcePasswordChange(false);
        $manager->persist($this->demoUsers[2]);

    }


}
