<?php
/**
 * Created by Eric Newbury.
 * Date: 6/12/16
 */

namespace EricNewbury\DanceVT\Util;


class MinPriorityQueue extends \SplPriorityQueue
{

    public function compare($priority1, $priority2)
    {
        if ($priority1 === $priority2) return 0;
        return $priority1 < $priority2 ? 1 : -1;
    }
}