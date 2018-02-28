<?php
/**
 * Created by Eric Newbury.
 * Date: 4/22/16
 */

namespace EricNewbury\DanceVT\Models\Persistence;


interface Profile
{
    function getId();
    function getName();
    function getImageLink();
    function getThumbLink();
    function getDescription();
    function getBlurb();
}