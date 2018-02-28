<?php
/**
 * Created by enewbury.
 * Date: 4/21/16
 */

namespace EricNewbury\DanceVT\Models\Response;


class ErrorResponse extends BaseResponse
{
    /**
     * ErrorResponse constructor.
     * @param string $errorMessage
     * @param string $devErrorMessage
     */
    public function __construct($errorMessage = null, $devErrorMessage = null)
    {
        parent::__construct(self::ERROR, null, $errorMessage, $devErrorMessage);
    }

}