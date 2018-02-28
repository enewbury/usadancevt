<?php
/**
 * Created by enewbury.
 * Date: 4/21/16
 */

namespace EricNewbury\DanceVT\Models\Response;


class FailResponse extends BaseResponse
{

    /**
     * FailResponse constructor.
     * @param null|string|array $msg
     */
    public function __construct($msg = null)
    {
        if($msg != null && is_string($msg)){
            parent::__construct(self::FAIL, ['message'=>$msg]);
        }
        else{
            parent::__construct(self::SUCCESS, ['messages'=>$msg]);
        }
    }
}