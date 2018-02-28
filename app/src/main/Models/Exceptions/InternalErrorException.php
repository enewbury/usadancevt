<?php
/**
 * Created by enewbury.
 * Date: 10/30/15
 */

namespace EricNewbury\DanceVT\Models\Exceptions;


class InternalErrorException extends \Exception
{
    private $devMessage;

    /**
     * InternalErrorException constructor.
     * @param string $message
     * @param string $devMessage
     */
    public function __construct($message = null, $devMessage = null)
    {
        $this->message = $message;
        $this->devMessage = $devMessage;
    }

    /**
     * @return mixed
     */
    public function getDevMessage()
    {
        return $this->devMessage;
    }

    /**
     * @param string $devMessage
     */
    public function setDevMessage($devMessage)
    {
        $this->devMessage = $devMessage;
    }


}