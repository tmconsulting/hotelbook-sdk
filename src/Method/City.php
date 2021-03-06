<?php

declare(strict_types=1);

namespace Hotelbook\Method;

use Hotelbook\Method\Builder\City as CityBuilder;
use Hotelbook\Method\Former\City as CityFormer;

/**
 * Dictionary - Get Cities method
 * Class City
 * @package App\Hotelbook\Method\Dictionary
 */
class City extends AbstractMethod
{
    /**
     * @param $params
     * @return CityResponse
     */
    public function handle($params)
    {
        $result = $this->connector->request('GET', 'cities', null, $params);
        return $this->getResultObject($result);
    }

    /**
     * @return string
     */
    protected function getBuilderClass()
    {
        return CityBuilder::class;
    }

    /**
     * @return string
     */
    protected function getFormerClass()
    {
        return CityFormer::class;
    }
}
