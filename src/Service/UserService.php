<?php

namespace K3\User\Service;

use Assert\AssertionFailedException;
use Doctrine\ORM\EntityManagerInterface;
use K3\User\Entity\ValueObjects\ChangePasswords;
use K3\User\Entity\ValueObjects\ComparePasswords;
use K3\User\Entity\ValueObjects\Email;
use K3\User\Entity\ValueObjects\Username;
use K3\User\Entity\User;
use K3\User\Entity\ValueObjects\Roles;
use K3\User\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

class UserService implements K3UserService
{
    private $entityManager;
    private $userRepository;
    private $user;

    public function __construct(EntityManagerInterface $manager, UserRepository $userRepository, User $user)
    {
        $this->entityManager = $manager;
        $this->user = $user;
        $this->userRepository = $userRepository;
    }

    /**
     * @param AttributeBag $data
     * @throws AssertionFailedException
     */
    public function createUser($data): void
    {
        $this->user->setUsername(new Username($data->get('username')));
        $this->user->setPlainPassword(new ComparePasswords($data->get('password', ''), $data->get('confirmPassword', '')));
        $this->user->setEmail(new Email($data->get('email')));
        $this->user->setActive((bool)$data->get('active', false));
        $roles = new Roles($data->get('roles'));
        $this->user->setRoles($roles->get());

        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
    }

    /**
     * @param int $id
     * @param AttributeBag $data
     * @throws AssertionFailedException
     */
    public function updateUser(int $id, $data): void
    {
        $user = $this->userRepository->getOneById($id);

        if ($data->has('password') && !empty($data->get('password'))) {
            $user->setPlainPassword(new ComparePasswords($data->get('password', ''), $data->get('confirmPassword', '')));
        }

        if ($data->has('email')) {
            $user->setEmail(new Email($data->get('email')));
        }

        if ($data->has('active')) {
            $user->setActive((bool)$data->get('active', false));
        }

        if ($data->has('roles')) {
            $roles = new Roles($data->get('roles'));
            $user->setRoles($roles->get());
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @param int $id
     */
    public function deleteUser(int $id): void
    {
        $user = $this->userRepository->getOneById($id);

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    /**
     * @param int $id
     * @param string $newPassword
     * @param string $confirmPassword
     * @param string $oldPassword
     * @throws AssertionFailedException
     */
    public function changeUserPassword(int $id, string $newPassword, string $confirmPassword, string $oldPassword): void
    {
        $user = $this->userRepository->getOneById($id);

        $savedPassword = $user->getPassword();

        $user->setPlainPassword(new ChangePasswords($oldPassword, $newPassword, $confirmPassword, $savedPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
