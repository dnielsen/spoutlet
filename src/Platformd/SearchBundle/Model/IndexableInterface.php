<?php

namespace Platformd\SearchBundle\Model;


interface IndexableInterface
{
    function getSearchFacetType(); // Used to group results together, i.e. Group and Global events are both just "event"
    function getSearchEntityType(); // Used to identify the entity class to look up the entity using
    function getSearchId();
    function getSearchTitle();
    function getSearchBlurb();
    function getSearchDate();
    function getDeleteSearchDocument();
    function getSites();
}
