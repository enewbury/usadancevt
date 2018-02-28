<?php
namespace EricNewbury\DanceVT\Models\Request;
use EricNewbury\DanceVT\Models\Exceptions\InternalErrorException;
use EricNewbury\DanceVT\Models\Request\Components\ListItem;

/**
 * Created by Eric Newbury.
 * Date: 4/22/16
 */
class AssociationsUpdateRequest
{

    /** @var int $id */
    public $id;

    /** @var  string $association */
    public $association;

    /** @var  string $active */
    public $active;

    /** @var  ListItem[] */
    public $organizations;

    /** @var  ListItem[] */
    public $instructors;

    /** @var  ListItem[] */
    public $events;

    /**
     * AssociationsUpdateRequest constructor.
     * @param $request
     * @throws InternalErrorException
     */
    public function __construct($request = null)
    {
        if($request != null) {
            foreach ($request as $key => $value) {
                if (isSet($value) && is_array($value)) {
                    $items = [];
                    foreach ($value as $listItem) {
                        $item = new ListItem();
                        foreach ($listItem as $itemKey => $itemValue) {
                            if (property_exists(ListItem::class, $itemKey)) {
                                $item->{$itemKey} = $itemValue;
                            } else {
                                throw new InternalErrorException('There was an error while updating.');
                            }
                        }
                        $items[] = $item;
                    }
                    $this->{$key} = $items;
                } else if (isSet($value) && property_exists(self::class, $key)) {
                    $this->{$key} = $value;
                } else {
                    throw new InternalErrorException('There was an error while updating.');
                }

            }
        }
    }
}