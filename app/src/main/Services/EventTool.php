<?php
/**
 * Created by enewbury.
 * Date: 3/13/16
 */

namespace EricNewbury\DanceVT\Services;


use DateTime;
use EricNewbury\DanceVT\Models\Exceptions\ClientErrorException;
use EricNewbury\DanceVT\Models\Exceptions\Error404Exception;
use EricNewbury\DanceVT\Models\Exceptions\InternalErrorException;
use EricNewbury\DanceVT\Models\Persistence\Category;
use EricNewbury\DanceVT\Models\Persistence\EventException;
use EricNewbury\DanceVT\Models\Persistence\Organization;
use EricNewbury\DanceVT\Models\Response\BaseResponse;
use EricNewbury\DanceVT\Models\Persistence\Event;
use EricNewbury\DanceVT\Models\Persistence\Instructor;
use EricNewbury\DanceVT\Models\Persistence\User;
use EricNewbury\DanceVT\Models\Response\SuccessResponse;
use EricNewbury\DanceVT\Services\Mail\MailService;
use EricNewbury\DanceVT\Util\DateTool;
use EricNewbury\DanceVT\Util\MinPriorityQueue;
use EricNewbury\DanceVT\Util\Validator;
use Exception;
use Monolog\Logger;
use Respect\Validation\Rules\Date;
use Respect\Validation\Rules\Even;
use Slim\Exception\NotFoundException;

class EventTool
{
    const DEFAULT_LIMIT = 10;

    /** @var  PersistenceService $persistenceService */
    private $persistenceService;

    /** @var  AssociationsTool $adminService */
    private $adminService;

    /** @var  MailService $mailService */
    private $mailService;

    /** @var  Validator $validator */
    private $validator;

    /** @var  \HTMLPurifier $htmlPurifier */
    private $htmlPurifier;

    /** @var  Logger logger */
    private $logger;

    private static $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    /**
     * EventTool constructor.
     * @param PersistenceService $persistenceService
     * @param AssociationsTool $adminService
     * @param MailService $mailService
     * @param Validator $validator
     * @param \HTMLPurifier $htmlPurifier
     * @param Logger $logger
     */
    public function __construct(PersistenceService $persistenceService, AssociationsTool $adminService, MailService $mailService, Validator $validator, \HTMLPurifier $htmlPurifier, Logger $logger)
    {
        $this->persistenceService = $persistenceService;
        $this->adminService = $adminService;
        $this->mailService = $mailService;
        $this->validator = $validator;
        $this->htmlPurifier = $htmlPurifier;
        $this->logger=$logger;
    }

    /**
     * @param array $filters
     * @param int|null $limit
     * @param bool $activeOnly
     * @return \EricNewbury\DanceVT\Models\Persistence\Event[]
     */
    public function loadEvents($filters, $limit = null, $activeOnly = false){
        $start = isSet($filters['start']) ? $filters['start'] : new DateTime();
        if(!isSet($limit) && !isSet($filters['end'])) $limit = self::DEFAULT_LIMIT;
        $end = isset($filters['end']) ? $filters['end'] : new DateTime('+100 years');

        $events = $this->persistenceService->getEvents($start, $end, $filters['searchQuery'], $filters['organizations'], $filters['instructors'], $filters['categories'], $filters['counties'], $activeOnly);
        
        //create and fill queue
        $heap = new MinPriorityQueue();
        foreach($events as $event){
            if($event->isRepeating()){
                $event = $this->generateNextInstance($event, $start, $end, true);
            }
            if ($event != null) {
                $heap->insert($event, $event->getStartDatetime());
            }
        }
        
        //clear events for output
        $events = [];
        
        //extract events until heap is empty or limit reached
        while($heap->valid() && (!$limit || count($events) < $limit)){
            //extract top and add to output
            $curEvent = $heap->extract();
            $events[] = $curEvent;

            //if it's a repeating event and oneInstance mode is off, add next instance (if there is one) to heap
            if($curEvent->isRepeating()){
                $nextInstance = $this->generateNextInstance($curEvent, $start, $end);
                if($nextInstance != null){
                    $heap->insert($nextInstance, $nextInstance->getStartDatetime());
                }
            }
        }
        
        return $events;
    }
   
    
    
    public function getNextSignatureEvent($activeOnly){
        /** @var Event[] $events */
        $events = $this->persistenceService->getNextSignatureEvent($activeOnly);
        if(empty($events)){ return null; }
        $event = $events[0];
        if($event->isRepeating()){
            $this->setTimeToNextInstance($event);
        }
        return $event;
    }


    /**
     * @param Event $event
     * @param DateTime $startRange
     * @param DateTime $endRange
     * @param bool $includeToday
     * @return Event|null
     */
    private function generateNextInstance(Event $event, DateTime $startRange, DateTime $endRange, $includeToday = false){
        //set $nextInstanceDate to start of range or events starttime, whichever is later to minimize steps through 'next' time function
        $nextInstanceDate = ($startRange > $event->getStartDatetime()) ? clone $startRange : clone $event->getStartDatetime();
        if($includeToday) $nextInstanceDate->modify("-1 day");
        $startDay = intval($nextInstanceDate->format('w'));

        //get day indexes
        $dayIndexes = explode(',', $event->getRepeatDays());

        $firstWeek = true;
        while (true) {
            //foreach repeatDay
            foreach ($dayIndexes as $day) {
                //for first week, only consider weekdays on or after the start day
                if($firstWeek){
                    if($day < $startDay) continue;
                }

                //set next date for day
                $nextInstanceDate->modify('next '.self::$weekdays[$day] . ' ' . $event->getStartDatetime()->format('H:i'));

                //exit if past end range, or repeatUntil is set and instance is past it
                if ($nextInstanceDate > $endRange || ($event->getRepeatUntil() !== null && $nextInstanceDate > $event->getRepeatUntil())){
                    return null;
                }

                //create new instance
                $event = clone $event;
                $start = clone $event->getStartDatetime();

                $event->setStartDatetime($start->add($start->diff($nextInstanceDate)));
                if($event->getEndDatetime() != null){
                    $end = clone $event->getEndDatetime();
                    $event->setEndDatetime($end->add($end->diff($nextInstanceDate)));
                }

                //only return event if this time is not in the exceptions list, otherwise, keep going
                if(!isSet($event->getExceptionsMap()[$event->getStartDatetime()->getTimestamp()])) {
                    return $event;
                }
            }
            $firstWeek = false;
        }
    }

    /**
     * @param $eventId
     * @param $instanceDate
     * @param bool $activeOnly
     * @return Event
     * @throws Error404Exception
     */
    public function loadEvent($eventId, $instanceDate, $activeOnly = false)
    {
        $event = $this->persistenceService->getEvent($eventId);
        if ($activeOnly && $event != null && !$event->isActive()) $event = null;
        if ($event !== null && $instanceDate != NULL && is_numeric($instanceDate)) {
            //check if this event is out of range
            if(($event->getRepeatUntil() !=null && $event->getRepeatUntil()->getTimestamp() < $instanceDate) || isSet($event->getExceptionsMap()[$instanceDate]))
            {
                throw new Error404Exception;
            }
            $start = new \DateTime();
            $start->setTimestamp($instanceDate);
            $start->setTime($event->getStartDatetime()->format('H'), $event->getStartDatetime()->format('i'));
            if($event->getEndDatetime() != null) {
                $durationInterval = $event->getStartDatetime()->diff($event->getEndDatetime());
                $end = clone $start;
                $end->add($durationInterval);
                $event->setEndDatetime($end);
            }
            else{
                $event->setEndDatetime(null);
            }
            $event->setStartDatetime($start);

        }
        return $event;
    }

    /**
     * @param $data
     * @param User $user
     * @param Event|null $event
     * @return BaseResponse
     */
    public function processEvent($data, $user, $event = null){
        //replace blank with null
        foreach ($data as $key => $value) {
            if ($value === "") {
                $data[$key] = null;
            }
        }

        $requestStartTime = date_create($data['startDate'] . ' ' . $data['startTime']);

        //new event
        if ($event == null) {
            $event = new Event();
            $this->persistenceService->persistItem($event);
            $this->persistenceService->persistChanges();
            return $this->updateEvent($data, $user, $event, true);
        }
        //repeating event, update only this event
        else if (isSet($data['repeatSelection']) && $data['repeatSelection'] == 'this'){

            //create new event;
            $newEvent = clone $event;
            $data['repeating'] = 'off';
            $data['repeatDays'] = null;
            $data['repeatUntil'] = null;
            $this->persistenceService->persistItem($newEvent);
            $this->persistenceService->persistChanges();
            //duplicate the asociations
            $this->persistenceService->duplicateEventAssociations($user, $event, $newEvent);
            $res = $this->updateEvent($data, $user, $newEvent, true);
            if($res->isSuccessful()){
                //remove occurrence of original event at this time
                $exception = new EventException();
                $exception->setEvent($event);
                $exception->setDatetime($newEvent->getStartDatetime());
                $this->persistenceService->persistItem($exception);
                $this->persistenceService->persistChanges();
            }
            return $res;
        }
        //repeating event update all future events
        else if(isSet($data['repeatSelection']) && $data['repeatSelection'] == 'all' && $requestStartTime != $event->getStartDatetime()){
            //create the future event
            $newEvent = clone $event;
            $this->persistenceService->persistItem($newEvent);
            $this->persistenceService->persistChanges();
            //duplicate the associations
            $this->persistenceService->duplicateEventAssociations($user, $event, $newEvent);
            $res = $this->updateEvent($data, $user, $newEvent, true);
            //if new item was created, set old events repeatUntil to 1 minute before start
            if($res->isSuccessful()) {
                $repeatUntil = clone $newEvent->getStartDatetime();
                $repeatUntil->sub(new \DateInterval('PT1M'));
                $event->setRepeatUntil($repeatUntil);
                $this->persistenceService->persistChanges();
            }
            return $res;
        }
        //non repeating event or first instance, update this event.
        else{
            return $this->updateEvent($data, $user, $event, false);
        }
    }

    /**
     * @param array $data
     * @param User $user
     * @param Event $event
     * @param $new
     * @return BaseResponse
     * @throws Exception
     */
    private function updateEvent($data, $user, $event, $new)
    {

        $event->setName($data['name']);
        $event->setActive((isSet($data['active']) && $data['active'] == 'on'));
        $event->setImageLink($data['imageLink']);
        $event->setThumbLink($data['thumbLink']);
        $event->setAllDay((isSet($data['allDay']) && $data['allDay'] == 'on'));
        $event->setSignatureEvent((isSet($data['signatureEvent']) && $data['signatureEvent'] == 'on'));
        $event->setRepeating((isSet($data['repeating']) && $data['repeating'] == 'on'));
        $event->setRepeatDays($data['repeatDays']);
        $event->setLocation($data['location']);
        $event->setCoordinates($data['coordinates']);
        $event->setCounty($data['county']);
        /** @var Category $category */
        $category = $this->persistenceService->getCategory($data['categoryId']);
        $event->setCategory($category);
        $facebook = ($data['facebook'] === null || substr($data['facebook'], 0, 4) === "http" ) ? $data['facebook'] : 'http://'.$data['facebook'];
        $event->setFacebook($facebook);
        if(preg_replace('(\r?\n)'," ", $data['blurb']) != $event->getBlurb() ){
            $event->setBlurb(substr($data['blurb'], 0, 254));
        }
        $event->setDescription($this->htmlPurifier->purify($data['description']));


        try {
            $this->validator->validateDateTime($data['startDate'], $data['startTime'], false);
            $this->validator->validateDateTime($data['endDate'], $data['endTime'], true);

            $event->setStartDatetime(new DateTime($data['startDate'] . ' ' . $data['startTime']));
            if($data['endDate'] !== null) {
                $event->setEndDatetime(new DateTime($data['endDate'] . ' ' . $data['endTime']));
            }else{
                $event->setEndDatetime(null);
            }
            $this->validator->validateDateTime($data['repeatUntil'], null, true);
            if($data['repeatUntil'] !== null) {
                $event->setRepeatUntil(new DateTime($data['repeatUntil']));
            }else{
                $event->setRepeatUntil(null);
            }
            

            //make sure id is set for new items
            $hostRequest = json_decode($data['hostRequest'], true);
            $instructorRequest = json_decode($data['instructorRequest'], true);
            $hostRequest['id']=$event->getId();
            $instructorRequest['id']=$event->getId();
            
            $this->adminService->updateAssociations($hostRequest, $user, $new);
            $this->adminService->updateAssociations($instructorRequest, $user, $new);
            $this->validator->validateEvent($event);
            $this->persistenceService->persistChanges();
            $this->persistenceService->refresh($event);

            $res = new BaseResponse();
            $date = ($event->isRepeating()) ? $event->getStartDatetime()->getTimestamp() : null;
            $res->setStatus(BaseResponse::SUCCESS)->setData(['message' => 'Updated Successfully', 'eventId' => $event->getId(), 'new'=>$new, 'date'=>$date]);
            return $res;
        } catch (ClientErrorException $e) {
            if($new){
                $this->persistenceService->removeAllEventAssociations($event);
                $this->persistenceService->deleteEntity(Event::class, $event->getId());
            }
            return BaseResponse::generateClientErrorResponse($e);
        }
        catch(InternalErrorException $e){
            if($new){
                $this->persistenceService->removeAllEventAssociations($event);
                $this->persistenceService->deleteEntity(Event::class, $event->getId());
            }
            return BaseResponse::generateInternalErrorResponse($e);
        }
        catch(Exception $e){
            $this->logger->error("Error updating or creating event", array('exception' => $e));
            if($new){
                $this->persistenceService->removeAllEventAssociations($event);
                $this->persistenceService->deleteEntity(Event::class, $event->getId());
            }
            throw $e;
        }

    }

    public function updateActivation($user, $id, $isOn, $repeatSelection, $instanceTimestamp){

        $event = $this->persistenceService->getEvent($id);
        if(isSet($repeatSelection) && $repeatSelection == 'this'){
            $newEvent = $this->cloneEventAndMakeException($user, $event, $instanceTimestamp);
            $res = $this->doActivationUpdate($newEvent, $isOn);
        }
        else if(isSet($repeatSelection) && $repeatSelection == 'all' && $instanceTimestamp != $event->getStartDatetime()->getTimestamp()){
            $newEvent = $this->cloneEventForFuture($user, $event, $instanceTimestamp);
            $res = $this->doActivationUpdate($newEvent, $isOn);
        }
        else{
            $res = $this->doActivationUpdate($event, $isOn);
        }

        $this->persistenceService->persistChanges();

        $res->setStatus(BaseResponse::SUCCESS);
        return $res;
    }

    /**
     * @param Event $event
     * @param bool $isOn
     * @return BaseResponse
     */
    private function doActivationUpdate($event, $isOn){
        $res = new BaseResponse();
        if ($isOn){
            $event->setActive(true);
            $res->setData(['message'=>'Event Activated']);
        }
        else{
            $event->setActive(false);
            $res->setData(['message'=>'Event Deactivated']);
        }
        return $res;
    }

    /**
     * @param User $user
     * @param Event $event
     * @param int $instanceTimestamp
     * @return mixed
     */
    private function cloneEventAndMakeException($user, $event, $instanceTimestamp){
        $newEvent = clone $event;

        $newEvent->setStartDatetime($newEvent->getStartDatetime()->setTimestamp($instanceTimestamp));

        if($newEvent->getEndDatetime() != null) {
            $diff = $event->getStartDatetime()->diff($event->getEndDatetime());
            $end = clone $newEvent->getStartDatetime();
            $newEvent->setEndDatetime($end->add($diff));
        }
        $newEvent->setRepeatUntil(null);
        $newEvent->setRepeating(false);
        $newEvent->setRepeatDays(null);
        $this->persistenceService->persistItem($newEvent);
        $this->persistenceService->duplicateEventAssociations($user, $newEvent, $newEvent);

        $this->makeException($event, $instanceTimestamp);
        return $newEvent;
    }

    /**
     * @param Event $event
     * @param $instanceTimestamp
     */
    private function makeException($event, $instanceTimestamp){
        //remove occurrence of original event at this time
        $exception = new EventException();
        $exception->setEvent($event);
        $start = clone $event->getStartDatetime();
        $exception->setDatetime($start->setTimestamp($instanceTimestamp));
        $this->persistenceService->persistItem($exception);
        $this->persistenceService->persistChanges();
    }

    /**
     * @param User $user
     * @param Event $event
     * @param int $instanceTimestamp
     * @return Event
     */
    private function cloneEventForFuture($user, $event, $instanceTimestamp){
        //create the future event
        $newEvent = clone $event;

        $newEvent->setStartDatetime($newEvent->getStartDatetime()->setTimestamp($instanceTimestamp));

        if($newEvent->getEndDatetime() != null) {
            $diff = $event->getStartDatetime()->diff($event->getEndDatetime());
            $end = clone $newEvent->getStartDatetime();
            $newEvent->setEndDatetime($end->add($diff));
        }

        $this->persistenceService->persistItem($newEvent);
        $this->persistenceService->duplicateEventAssociations($user, $newEvent, $newEvent);

        $repeatUntil = clone $newEvent->getStartDatetime();
        $repeatUntil->sub(new \DateInterval('PT1M'));
        $event->setRepeatUntil($repeatUntil);
        $this->persistenceService->persistChanges();
        return $newEvent;
    }

    public function deleteEvent($user, $eventId, $repeatSelection, $instanceTimestamp){
    $event = $this->persistenceService->getEvent($eventId);
        if(isSet($repeatSelection) && $repeatSelection == 'this'){
            $this->makeException($event, $instanceTimestamp);
        }
        else if(isSet($repeatSelection) && $repeatSelection == 'all' && $instanceTimestamp != $event->getStartDatetime()->getTimestamp()){
            $start = $event->getStartDatetime();
            $start->setTimestamp($instanceTimestamp);
            $event->setRepeatUntil($start);
            $this->persistenceService->persistChanges();
        }
        else {
            $this->persistenceService->deleteEvent($eventId);
        }
        //delete associations
        return new SuccessResponse('Event deleted.');
    }

    /**
     * @param Event $event
     */
    private function setTimeToNextInstance($event){
        //get day indexes
        $dayIndexes = explode(',', $event->getRepeatDays());
        sort($dayIndexes);
        $day = $dayIndexes[0];
        $nextInstanceDate = new DateTime();
        $nextInstanceDate->sub(new \DateInterval('P1D'));

        while(true){
            //set next date for day
            $nextInstanceDate->modify('next ' . self::$weekdays[$day] . ' ' . $nextInstanceDate->format('H:i'));


            //only add if this time is not in the exceptions list
            if (!isSet($event->getExceptionsMap()[$event->getStartDatetime()->getTimestamp()])) {
                //update times
                $start = $event->getStartDatetime();
                $end = $event->getEndDatetime();
                $event->setStartDatetime($start->add($start->diff($nextInstanceDate)));
                if ($end != null) {
                    $event->setEndDatetime($end->add($end->diff($nextInstanceDate)));
                }
                return;
            }
        }
        
    }
}
