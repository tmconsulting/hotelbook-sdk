<?php
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 22.05.16
 * Project: provider_hotelbook
 */

declare(strict_types=1);

namespace App\Hotelbook\Method\Dynamic;

use App\Hotelbook\Connector\ConnectorInterface;
use App\Hotelbook\Object\Hotel\SearchPassenger;
use App\Hotelbook\Object\Results\SearchResult;
use Carbon\Carbon;
use SimpleXMLElement;
use App\Hotelbook\Method\AbstractMethod;

class Search extends AbstractMethod
{
    const DATE_FORMAT = 'Y-m-d';
    const TIMEOUT = 1;

    /**
     * @var \App\Hotelbook\Connector\ConnectorInterface
     */
    private $connector;

    /**
     * SearchResult constructor.
     *
     * @param \App\Hotelbook\Connector\ConnectorInterface $connector
     */
    public function __construct(ConnectorInterface $connector)
    {
        $this->connector = $connector;
    }

    /**
     * @param $params
     * @return mixed
     */
    public function build($params)
    {
        /** @var Carbon $checkInDate */
        /** @var SearchPassenger[] $rooms */
        [$value, $checkInDate, $checkOutDate, $rooms] = $params;

        $xml = new SimpleXMLElement('<HotelSearchRequest/>');
        $request = $xml->addChild('Request');
        $request->addAttribute('cityId', (string)$value);

        $request->addAttribute('checkIn', $checkInDate->format(self::DATE_FORMAT));
        $request->addAttribute('duration', (string)$checkInDate->diffInDays($checkOutDate));
        $request->addAttribute('confirmation', 'online');
        $request->addAttribute('limitResults', '100');

        $roomsXml = $xml->addChild('Rooms');

        $i = 0;
        foreach ($rooms as $room) {
            $roomXmlChild = $roomsXml->addChild('Room');
            $roomXmlChild->addAttribute('roomNumber', (string)++$i);
            $roomXmlChild->addAttribute('adults', (string)$room->getAdults());

            if ($room->getCountOfChildrens() > 0) {
                $roomXmlChild->addAttribute('children', (string)$room->getCountOfChildrens());
                foreach ($room->getChildrens() as $age) {
                    $roomXmlChild->addChild('ChildAge', (string)$age);
                }
            }
        }

        return $xml->asXML();
    }

    protected function preHandle($results)
    {
        return $this->connector->request('POST', 'hotel_search', $results, ['query' => ['async' => 1, 'timeout' => 5]]);
    }

    /**
     * @param $results <- builds results
     * @return mixed
     */
    public function handle($results)
    {
        $preResponse = $this->preHandle($results);

        do {
            $response = $this->connector->request('GET', 'hotel_search_async', null, [
                'query' =>
                    [
                        'search_id' => (int)$preResponse->HotelSearchId,
                        'limit_results' => 10
                    ]
            ]);
            usleep(100000);
        } while (
            (string)$response->Hotels->attributes()['searchingIsCompleted'] !== 'true'
            &&
            empty($this->getErrors($response))
        );

        $values = [];

        $errors = $this->getErrors($response);

        if (empty($errors)) {
            $values = $this->form([$preResponse, $response]);
        }

        return new SearchResult($values, $errors);
    }


    /**
     * Метод для формирования ответа из ответа XML //TODO сделать такой во всех методах
     * @param $response
     * @return array
     */
    public function form($data)
    {
        [$preResponse, $response] = $data;
        $i = 0;
        $array = [];
        $search = current($response->HotelSearch);

        $searchRooms = [];
        foreach ($preResponse->HotelSearchRequest->Rooms->Room as $room) {
            $attributes = current($room);

            $ages = [];
            foreach ($room->ChildAge as $age) {
                $ages[] = (string)$age;
            }

            $searchRooms[] = [
                'adults' => $attributes['adults'],
                'children' => isset($attributes['children']) ? $attributes['children'] : "0",
                'ages' => $ages
            ];
        }

        foreach ($response->Hotels->Hotel as $hotel) {
            if (!isset($hotel->Rooms->Room)) {
                continue;
            }

            $value = current($hotel);
            $money = $this->money($value['price'], $value['currency']);

            $array[] = [
                'searchId' => (string)$search['searchId'],
                'resultId' => (string)$value['resultId'],
                'hotelId' => (int)$value['hotelId'],
                'name' => (string)$value['hotelName'],
                'subProviderId' => (int)$value['providerId'],
                'address' => (string)$value['hotelAddress'],
                'stars' => (int)$value['hotelCatName'],
                'image' => (string)$value['hotelPhotoUrl'],
                'coords' => [
                    'lat' => isset($value['hotelLatitude']) ? (float)$value['hotelLatitude'] : null,
                    'lng' => isset($value['hotelLongitude']) ? (float)$value['hotelLongitude'] : null,
                ],
                'price' => [
                    'sum' => $money->getAmount(),
                    'currency' => $money->getCurrency(),
                    //@link: http://xmldoc.hotelbook.ru/ru/hotels/hotel-search.html?highlight=useNds
                    'vat' => to_bool(array_get($value, 'useNds')),
                    'noOpenSale' => !to_bool(array_get($value, 'noOpenSale')),
                    'quotaAmount' => $this->money((string)array_get($value, 'quotaAmount'), $value['currency'])->getAmount(),
                    'rackRate' => $this->money((string)array_get($value, 'priceRackRate'), $value['currency'])->getAmount(),
                ],
            ];

            foreach ($hotel->Rooms->Room as $room) {
                $room = current($room);
                $array[$i]['rooms'][] = [
                    'number' => (int)$room['roomNumber'],
                    'sizeId' => (int)$room['roomSizeId'],
                    'sizeName' => (string)$room['roomSizeName'],
                    // 'sizeDescription' => (string) $room['roomSizeDescription'],
                    'typeId' => (int)$room['roomTypeId'],
                    'typeName' => (string)$room['roomTypeName'],
                    //'typeDescription' => (string) $room['roomTypeDescription'],
                    'viewId' => (int)$room['roomViewId'],
                    'viewName' => (string)$room['roomViewName'],
                    'mealId' => (int)$room['mealId'],
                    'cots' => (int)$room['cots'],
                    'children' => (int)$room['children'],
                    // 'viewDescription' => (string) $room['roomViewDescription'],
                ];
            }

            $array[$i]['searchRooms'] = $searchRooms;

            $i++;
        }


        return $array;
    }
}