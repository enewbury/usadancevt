<?php
/**
 * Created by enewbury.
 * Date: 4/21/16
 */

namespace EricNewbury\DanceVT\Models\Response;


class SuccessResponse extends BaseResponse
{

    public function __construct($data = null)
    {
        if($data != null && is_string($data)){
            parent::__construct(self::SUCCESS, ['message'=>$data]);
        }
        else{
            parent::__construct(self::SUCCESS, $data);
        }
    }
}