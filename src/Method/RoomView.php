<?php

declare(strict_types=1);

namespace Hotelbook\Method;

use Hotelbook\Method\Builder\BaseBuilder;
use Hotelbook\Method\Former\RoomView as RoomViewFormer;

/**
 * A method to fetch all room view types.
 * Class RoomView
 * @package App\Hotelbook\Method\Dictionary
 */
class RoomView extends AbstractMethod
{
    /**
     * @param $params
     * @return HotelTypeResponse
     */
    public function handle($params)
    {
        $results = $this->connector->request('GET', 'room_view');
        return $this->getResultObject($results);
    }

    /**
     * @return string
     */
    protected function getBuilderClass()
    {
        return BaseBuilder::class;
    }

    /**
     * @return string
     */
    protected function getFormerClass()
    {
        return RoomViewFormer::class;
    }
}
