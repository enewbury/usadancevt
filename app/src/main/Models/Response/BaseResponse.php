<?php
/**
 * Created by enewbury.
 * Date: 10/25/15
 */

namespace EricNewbury\DanceVT\Models\Response;


use EricNewbury\DanceVT\Models\Exceptions\ClientErrorException;
use EricNewbury\DanceVT\Models\Exceptions\InternalErrorException;

class BaseResponse
{
    const SUCCESS = 'success';
    const FAIL = 'fail';
    const ERROR = 'error';

    private $status;
    private $data;
    private $errorMessage;
    private $devErrorMessage;

    public function __construct($status = null, $data = null, $errorMessage=null, $devErrorMessage = null)
    {
        $this->status=$status;
        $this->data = $data;
        $this->errorMessage = $errorMessage;
        $this->devErrorMessage = $devErrorMessage;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @param mixed $errorMessage
     * @return $this
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDevErrorMessage()
    {
        return $this->devErrorMessage;
    }

    /**
     * @param mixed $devErrorMessage
     * @return $this
     */
    public function setDevErrorMessage($devErrorMessage)
    {
        $this->devErrorMessage = $devErrorMessage;
        return $this;
    }


    public function __toString(){
        return json_encode(get_object_vars($this));
    }

    public static function generateClientErrorResponse(ClientErrorException $e){
        $response = new BaseResponse();
        $response->setStatus(BaseResponse::FAIL);
        $response->setData([
            'message'=>$e->getMessage(),
            'messages'=>$e->getMessages()
        ]);
        return $response;
    }
    public static function generateInternalErrorResponse(InternalErrorException $e){
        $response = new BaseResponse();
        $response->setStatus(BaseResponse::ERROR);
        $response->setErrorMessage($e->getMessage());
        $response->setDevErrorMessage($e->getDevMessage());

        return $response;
    }

    public static function message($message){
        return ['message'=>$message];
    }

    /**
     * @return bool
     */
    public function isSuccessful(){
        return ($this->status === self::SUCCESS) ? true : false;
    }

    public function toArray(){
        return array(
            'status'=>$this->status,
            'data'=>$this->data,
            'errorMessage'=>$this->errorMessage,
            'devErrorMessage'=>$this->devErrorMessage
        );
    }
}