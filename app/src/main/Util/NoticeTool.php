<?php
/**
 * Created by enewbury.
 * Date: 4/21/16
 */

namespace EricNewbury\DanceVT\Util;


class NoticeTool
{
    public static function generateNotice($title, $notice, $linkText=null, $linkHref = null){
        $notice = [
            'title'=>$title,
            'notice'=>$notice
        ];
        if ($linkText !==null && $linkHref !== null){
            $notice['linkText']=$linkText;
            $notice['linkHref']=$linkHref;
        }
        return $notice;
    }
}