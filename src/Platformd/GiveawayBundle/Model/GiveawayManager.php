<?php

namespace Platformd\GiveawayBundle\Model;

use Doctrine\Common\Persistence\ObjectManager;
use Platformd\UserBundle\Entity\User;
use Platformd\GiveawayBundle\Model\GiveawayKeyRequest;
use Platformd\GiveawayBundle\Entity\MachineCodeEntry;
use Platformd\GiveawayBundle\Model\Exception\MissingKeyException;

/**
 * Service class for dealing with the giveaway system
 */
class GiveawayManager
{
    private $em;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    /**
     * Returns all the giveaway key requests for a user:
     *
     *      a) All GiveawayKey objects assigned to this user
     * PLUS
     *      b) All MachineCodeEntry objects assigned to this user but not approved
     *
     * @param \Platformd\UserBundle\Entity\User $user
     * @return \Platformd\GiveawayBundle\Entity\GiveawayKeyRequest[]
     */
    public function getGiveawayKeyRequestsForUser(User $user)
    {
        $keys = $this->getGiveawayKeyRepository()->findAssignedToUser($user);
        $machineCodes = $this->getMachineCodeEntryRepository()->findAssignedToUserWithoutGiveawayKey($user);

        return array_merge(
            $requests = $this->convertKeysToRequests($keys),
            $this->convertMachineCodesToRequests($machineCodes)
        );
    }

    /**
     * Approves the machine code entry and associates it with a GiveawayKey
     *
     * @param \Platformd\GiveawayBundle\Entity\MachineCodeEntry $machineCode
     */
    public function approveMachineCode(MachineCodeEntry $machineCode)
    {
        // see if it's already assigned to a key
        if ($machineCode->getKey()) {
            return;
        }

        $pool = $machineCode->getGiveaway()->getActivePool();

        $key = $this->getGiveawayKeyRepository()->getUnassignedKey($pool);
        if (!$key) {
            throw new MissingKeyException();
        }

        // attach the key, then attach it to the machine code
        $key->assign($machineCode->getUser(), $machineCode->getIpAddress(), $machineCode->getGiveaway()->getLocale());
        $machineCode->attachToKey($key);

        $this->em->persist($key);
        $this->em->persist($machineCode);
        $this->em->flush();
    }

    /**
     * @param \Platformd\GiveawayBundle\Entity\GiveawayKey[] $keys
     * @return \Platformd\GiveawayBundle\Entity\GiveawayKeyRequest[]
     */
    private function convertKeysToRequests(array $keys)
    {
        $requests = array();

        foreach ($keys as $key) {
            $requests[] = new GiveawayKeyRequest(
                $key->getValue(),
                $key->getPool()->getGiveaway(),
                MachineCodeEntry::STATUS_APPROVED,
                $key->getAssignedAt()
            );
        }

        return $requests;
    }

    /**
     * @param \Platformd\GiveawayBundle\Entity\MachineCodeEntry[] $machineCodes
     * @return \Platformd\GiveawayBundle\Model\GiveawayKeyRequest[]
     */
    private function convertMachineCodesToRequests(array $machineCodes)
    {
        $requests = array();

        foreach ($machineCodes as $code) {
            $requests[] = new GiveawayKeyRequest(
                null,
                $code->getGiveaway(),
                $code->getStatus(),
                null
            );
        }

        return $requests;
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\Repository\GiveawayKeyRepository
     */
    private function getGiveawayKeyRepository()
    {
        return $this->em->getRepository('GiveawayBundle:GiveawayKey');
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\MachineCodeEntryRepository
     */
    private function getMachineCodeEntryRepository()
    {
        return $this->em->getRepository('GiveawayBundle:MachineCodeEntry');
    }
}