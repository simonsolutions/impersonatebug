<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\ObjectHistory;
use App\Repository\UserRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Uid\Uuid;

class DatabaseActivityEventSubscriber implements EventSubscriber
{

    private TokenStorageInterface $tokenStorage;

    private UserRepository $userRepository;

    static array $ignoreEntitiesLastActions = [
        'ApiRequestCount',
        'LogEntry',
        'ObjectHistory',
        'BusinessDataStoragevalue',
        'ShippingParcel',
        'ShippingTrackingEvents',
    ];

    static array $ignoreEntitiesObjectHistory = [
        'ApiRequestCount',
        'LogEntry',
        'ObjectHistory',
        'BusinessDataStoragevalue',
        'SystemConfig',
        'ShippingParcel',
        'ShippingTrackingEvents',
        'UserPermission',
    ];

    static array $ignoreFields = [
        'CreatedBy',
        'CreatedAt',
        'UpdatedBy',
        'UpdatedAt',
        'LastLoginAt',
        'LastAction',
        'CronStatus',
        'LastRuntime',
        'LastEnd',
        'DeviceInfo',
        'DeviceSerial',
    ];

    static array $suppressValues = [
        'password',
    ];

    public function __construct(TokenStorageInterface $tokenStorage,
                                UserRepository $userRepository)
    {
        $this->tokenStorage = $tokenStorage;
        $this->userRepository = $userRepository;
    }

    private function getClassName($class): string|bool
    {
        if (str_starts_with(get_class($class), 'App\Entity\\')) {
            $tableClass = explode("\\", get_class($class));
            return strtolower(end($tableClass));
        }
        return false;
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        $tableName = $this->getClassName($entity);
        if ($tableName === false) {
            return;
        }
        if (in_array(strtolower($tableName), array_map('strtolower', $this::$ignoreEntitiesLastActions))) {
            return;
        }

        $entity->setCreatedAt(new \DateTime());
        $entity->setUpdatedAt(new \DateTime());
        if ($this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();
            $entity->setCreatedBy($user);
            $entity->setUpdatedBy($user);
        } else {
            return;
        }

    }

    public function preRemove(LifecycleEventArgs $args): void
    {

    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        $tableName = $this->getClassName($entity);
        if ($tableName === false) {
            return;
        }
        if (in_array(strtolower($tableName), array_map('strtolower', $this::$ignoreEntitiesLastActions))) {
            return;
        }

        $entity->setUpdatedAt(new \DateTime());
        if ($this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();
            $entity->setUpdatedBy($user);
        } else {
            return;
        }
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        //$this->logActivity('persist', $args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        //$this->logActivity('remove', $args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->logActivity('update', $args);
    }

    private function logActivity(string $action, LifecycleEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $entity = $args->getObject();

        $tableName = $this->getClassName($entity);
        if ($tableName === false) {
            return;
        }
        if (in_array(strtolower($tableName), array_map('strtolower', $this::$ignoreEntitiesObjectHistory))) {
            return;
        }
        $metadata = $em->getClassMetadata(get_class($entity));

        if ($this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();
        } else {
            return;
        }

        $changeSet = $em->getUnitOfWork()->getEntityChangeSet($entity);

        foreach ($changeSet as $fieldName => $singleChange) {

            if (!in_array(strtolower($fieldName), array_map('strtolower', $this::$ignoreFields))) {

                $fieldMetadata = ($metadata->fieldMappings[$fieldName] ?? []);
                $fieldType = ($fieldMetadata['type'] ?? 'string');
                $fieldLookup = ($fieldMetadata['options']['lookup'] ?? '');
                $translationPrefix = '';

                if ($fieldLookup == 'user_group') {

                    if (Uuid::isValid($singleChange[0])) {
                        $oldUser = $this->userRepository->findOneBy(['id' => $singleChange[0]]);
                        $oldValue = $oldUser?->__toString();
                    } else {
                        $translationPrefix = 'user.group.';
                        $oldValue = $singleChange[0];
                    }
                    if (Uuid::isValid($singleChange[1])) {
                        $newUser = $this->userRepository->findOneBy(['id' => $singleChange[1]]);
                        $newValue = $newUser->__toString();
                    } else {
                        $translationPrefix = 'user.group.';
                        $newValue = $singleChange[1];
                    }

                } else {
                    if ($fieldType == 'boolean') {
                        $oldValue = $singleChange[0] ? 'yes' : 'no';
                        $newValue = $singleChange[1] ? 'yes' : 'no';
                        $translationPrefix = 'global.';
                    } elseif ($fieldType == 'datetime') {
                        $oldValue = ($singleChange[0] != null ? $singleChange[0]->format('d.m.Y H:i:s') : '');
                        $newValue = ($singleChange[1] != null ? $singleChange[1]->format('d.m.Y H:i:s') : '');
                    } elseif ($fieldType == 'json') {
                        $oldValue = implode(', ', ($singleChange[0] ?? []));
                        $newValue = implode(', ', ($singleChange[1] ?? []));
                        $translationPrefix = ($fieldMetadata['options']['prefix'] ?? '');
                    } else {
                        $oldValue = (string)$singleChange[0];
                        $newValue = (string)$singleChange[1];
                        $translationPrefix = ($fieldMetadata['options']['prefix'] ?? '');
                    }
                }

                if ($oldValue != $newValue) {
                    $objectHistory = new ObjectHistory();
                    $objectHistory->setReferenceType($tableName);
                    $objectHistory->setReferenceId($entity->getId() !== null ? $entity->getId()->toRfc4122() : '');
                    $objectHistory->setChangedBy($user);
                    $objectHistory->setChangeDate(new \DateTime());
                    $objectHistory->setChangeField($fieldName);
                    if (!in_array(strtolower($fieldName), array_map('strtolower', $this::$suppressValues))) {
                        $objectHistory->setSourceType($fieldType);
                        $objectHistory->setTranslationPrefix($translationPrefix);
                        $objectHistory->setOldFieldValue($oldValue);
                        $objectHistory->setNewFieldValue($newValue);
                    }
                    $em->persist($objectHistory);
                    $em->flush();
                }
            }
        }

    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preRemove,
            Events::preUpdate,
            Events::postPersist,
            Events::postRemove,
            Events::postUpdate,
        ];
    }

}