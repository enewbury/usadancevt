<?php
/**
 * Created by Eric Newbury.
 * Date: 5/20/16
 */

namespace EricNewbury\DanceVT\Services;


use DateTime;
use EricNewbury\DanceVT\Models\Persistence\Category;
use EricNewbury\DanceVT\Models\Persistence\Instructor;
use EricNewbury\DanceVT\Models\Persistence\Organization;
use EricNewbury\DanceVT\Util\DateTool;
use Psr\Http\Message\ServerRequestInterface;
use Respect\Validation\Rules\Date;
use Slim\Http\Request;

class FilteringService
{

    /**
     * @var PersistenceService $persistenceService
     */
    private $persistenceService;

    /**
     * FilteringService constructor.
     * @param PersistenceService $persistenceService
     */
    public function __construct(PersistenceService $persistenceService)
    {
        $this->persistenceService = $persistenceService;
    }


    public function processFilters($postData, $noDateRange = false){
        //get filters
        $searchQuery = $organizations = $categories = $instructors = $startRange = $endRange = $counties = null;
        if(!empty($postData['searchQuery'])){
            $searchQuery = $postData['searchQuery'];
        }
        if(!empty($postData['startRange'])){
            $startRange = new DateTime($postData['startRange']);
        }
        if(!empty($postData['endRange'])){
            $endRange = new DateTime($postData['endRange'].' 23:59:59');
        }
        if(isSet($postData['organization']) && $postData['organization'] != -1){
            $organizations[] = $this->persistenceService->getReference(Organization::class, $postData['organization']);
        }
        if(isSet($postData['instructor']) && $postData['instructor'] != -1){
            $instructors[] = $this->persistenceService->getReference(Instructor::class, $postData['instructor']);
        }
        if(isSet($postData['county']) && $postData['county'] != -1){
            $counties[] = $postData['county'];
        }
        if(isSet($postData['category']) && $postData['category'] != -1){
            $categories[] = $this->persistenceService->getReference(Category::class, $postData['category']);
        }

        if(!$noDateRange) {
            if ($startRange === null) {
                $startRange = new \DateTime();
            }
            if ($endRange === null) {
                $endRange = new \DateTime("tomorrow +1 week -1 second");
            }
        }

        return [
            //template data
            [
                'searchQuery'=>$searchQuery,
                'filteringInstructor'=>(isSet($instructors[0])) ? $instructors[0] : null,
                'filteringOrganization'=>(isSet($organizations[0])) ? $organizations[0] : null,
                'filteringCategory'=>(isSet($categories[0])) ? $categories[0] : null,
                'filteringCounty'=>(isSet($counties[0])) ? $counties[0] : null,
                'start'=>$startRange,
                'end'=>$endRange,
                'DateTool'=>new DateTool()
            ],
            //filters
            [
                'searchQuery'=>$searchQuery,
                'instructors'=>$instructors,
                'organizations'=>$organizations,
                'counties'=>$counties,
                'categories'=>$categories,
                'start'=>$startRange,
                'end'=>$endRange
            ]
        ];
    }

    /**
     * @param ServerRequestInterface $httpReq
     * @param DateTime $start
     * @param DateTime $end
     * @return array
     */
    public function generateQueryString($httpReq, $start, $end)
    {
        $start->setTime(0, 0);
        $diff = $start->diff($end);
        $nextStart = clone $end; $nextStart->modify("+1 second");
        $nextEnd = clone $end; $nextEnd->add($diff);
        $prevStart = clone $start; $prevStart->sub($diff);
        $prevEnd = clone $start; $prevEnd->modify('-1 second');

        $data['query'] = $httpReq->getQueryParams();
        $data['query']['startRange'] = $nextStart->format('m/d/Y');
        $data['query']['endRange'] = $nextEnd->format('m/d/Y');
        $data['nextQuery'] = http_build_query($data['query']);

        $data['query']['startRange'] = $prevStart->format('m/d/Y');
        $data['query']['endRange'] = $prevEnd->format('m/d/Y');
        $data['prevQuery'] = http_build_query($data['query']);
        unset($data['query']);
        return $data;
    }
}