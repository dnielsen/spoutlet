<?php

namespace Platformd\SpoutletBundle\Model;


interface ReportableContentInterface
{
    function getContentReports();
    function setContentReports($contentReports);
    function setDeleted($deleted);
    function getDeleted();
    function setDeletedReason($deletedReason);
    function getDeletedReason();
}
