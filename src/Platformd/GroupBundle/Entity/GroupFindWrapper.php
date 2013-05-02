<?php

namespace Platformd\GroupBundle\Entity;

class GroupFindWrapper
{
    private $groupName;
    private $category;
    private $deleted;
    private $sites;
    private $startDate;
    private $endDate;

    public function setGroupName($value)
    {
        $this->groupName = $value;
    }

    public function getGroupName()
    {
        return $this->groupName;
    }

    public function setCategory($value)
    {
        $this->category = $value;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    public function getSites()
    {
        return $this->sites;
    }

    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }

    public function setEndDate($value)
    {
        $this->endDate = $value;
    }

    public function getEndDate()
    {
        return $this->endDate;
    }
}
