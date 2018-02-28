<?php
/**
 * Created by enewbury.
 * Date: 10/30/15
 */

namespace EricNewbury\DanceVT\Models\Exceptions;


class ClientErrorException extends \Exception
{

    /** @var array $messages*/
    private $messages;
    /** @var null|array $additionalData */
    private $additionalData;

    /**
     * ClientErrorException constructor.
     * @param string $message
     * @param array $messages
     * @param null $additionalData
     */
    public function __construct($message = null, $messages = null, $additionalData = null)
    {
        $this->message = $message;
        $this->messages = $messages;
        $this->additionalData = $additionalData;
    }

    /**
     * @return mixed
     */


    /**
     * @return mixed
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param mixed $messages
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;
    }

    /**
     * @return null | array
     */
    public function getAdditionalData()
    {
        return $this->additionalData;
    }

    /**
     * @param null|array $additionalData
     */
    public function setAdditionalData($additionalData)
    {
        $this->additionalData = $additionalData;
    }



}