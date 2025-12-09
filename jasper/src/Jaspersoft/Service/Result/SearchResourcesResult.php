<?php
namespace Jaspersoft\Service\Result;

use Jaspersoft\Dto\Resource\ResourceLookup;
require_once 'jasper/src/Jaspersoft/Dto/Resource/ResourceLookup.php';

/**
 * Class SearchResourcesResult
 * @package Jaspersoft\Service\Result
 */
class SearchResourcesResult
{

    /**
     * Items found by search
     *
     * @var array
     */
    public $items;
    /**
     * @var int
     */
    public $resultCount;
    /**
     * @var int
     */
    public $startIndex;
    /**
     * @var int
     */
    public $totalCount;

    public function __construct($itemData, $resultCount = null, $startIndex = null, $totalCount = null)
    {
        $this->createItemsFromData($itemData);
        $this->resultCount = $resultCount;
        $this->startIndex = $startIndex;
        $this->totalCount = $totalCount;
    }

    public function createItemsFromData($itemData)
    {
        if ($itemData !== null) {
            foreach ($itemData->resourceLookup as $rl)
                $this->items[] = ResourceLookup::createFromJSON($rl);
        } else {
            $this->items = array();
        }
    }

}