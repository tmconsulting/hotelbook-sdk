<?php

declare(strict_types=1);

namespace Hotelbook\Method\Builder;

use Hotelbook\Object\Contact;
use Hotelbook\Object\Hotel\BookItem;
use Hotelbook\Object\Hotel\BookPassenger;
use Hotelbook\Object\Hotel\Dictionary\Title;
use Hotelbook\Object\Hotel\Tag;

/**
 * Class Book (Builder
 * @package App\Hotelbook\Method\Builder\Dynamic
 */
class Book implements BuilderInterface
{
    /**
     * @param $params
     * @return mixed
     */
    public function build($params)
    {
        /** @var Contact $contact */
        /** @var BookItem[] $items */
        /** @var Tag $tag */
        [$contact, $items, $tag, $searchResult] = $params;

        $xml = new \SimpleXMLElement('<AddOrderRequest/>');

        $contactXml = $xml->addChild('ContactInfo');
        $contactXml->addChild('Name', $contact->getName());
        $contactXml->addChild('Email', $contact->getEmail());
        $contactXml->addChild('Phone', $contact->getPhone());
        $contactXml->addChild('Comment', $contact->getComment());

        $xml->addChild('Tag', $tag->getTag());

        $hotelItems = $xml->addChild('Items');

        foreach ($items as $item) {
            $hotel = $hotelItems->addChild('HotelItem');
            $search = $hotel->addChild('Search');
            $search->addAttribute('searchId', $item->getSearchId());
            $search->addAttribute('resultId', $item->getResultId());
            $hotel->addChild('PayForm', 'cashless'); // оплата безналом по умолчанию
            $roomsXml = $hotel->addChild('Rooms');

            foreach ($this->paxHandling($item, $searchResult) as $room) {
                $roomXml = $roomsXml->addChild('Room');
                /** @var BookPassenger $person */
                foreach ($room as $person) {
                    $pax = $roomXml->addChild('RoomPax');
                    $pax->addChild('Title', $person->getTitle());
                    $pax->addChild('FirstName', $person->getFirstName());
                    $pax->addChild('LastName', $person->getLastName());

                    if ($person->isChild()) {
                        $pax->addAttribute('child', 'true');
                        $pax->addAttribute('age', (string)$person->getAge());
                    }
                }
            }
        }

        return $xml->asXML();
    }

    /**
     * Pax handling.
     * Sorts children and adults in two groups.
     * @link http://xmldoc.hotelbook.ru/ru/hotels/add-order.html#roompax
     * @param \Hotelbook\Object\Hotel\BookItem $bookItem
     * @param $searchResult
     * @return \Hotelbook\Method\Collection
     */
    protected function paxHandling(BookItem $bookItem, $searchResult)
    {
        $childs = [];
        $adults = [];
        foreach ($bookItem->getRooms() as $index => $room) {
            foreach ($room as $person) {
                if ($person->isChild()) {
                    $childs[$index][] = $person;
                } else {
                    $adults[$index][] = $person;
                }
            }
        }

        if ($searchResult !== null) {
            $adults = $this->tbaAutoComplete($searchResult, $adults);
            $childs = $this->tbaAutoComplete($searchResult, $childs, true);
        }

        return collect($this->putChildrenToBottom($adults, $childs));
    }

    /**
     * This method autocompletes PAX's by TBA persons.
     * @param $payload
     * @param array $pax
     * @param bool $child
     * @return array
     */
    protected function tbaAutoComplete($payload, array $pax, $child = false)
    {
        $title = $child ? Title::CHILD : Title::MR;
        $name = $child ? 'TBA_CHILD_' : 'TBA_ADULT_';

        foreach ($payload['searchRooms'] as $roomKey => $room) {
            $count = !$child ? $room['adults'] : $room['children'];
            $currentCount = !isset($pax[$roomKey]) ? 0 : count($pax[$roomKey]);

            for ($i = $currentCount; $i < $count; $i++) {
                $pax[$roomKey][] = new BookPassenger(
                    $title,
                    $name . $i,
                    $name . $i,
                    $child,
                    $child ? true : false,
                    $child ? (int)$room['ages'][$i] : null
                );
            }
        }

        return $pax;
    }

    /**
     * Pax handling.
     * Puts children to the bottom
     * @link http://xmldoc.hotelbook.ru/ru/hotels/add-order.html#roompax
     * @param array $adults
     * @param array $childs
     * @return array
     */
    protected function putChildrenToBottom(array $adults, array $childs)
    {
        $results = [];
        foreach ($adults as $roomIndex => $human) {
            if (isset($childs[$roomIndex])) {
                $results[$roomIndex] = array_merge($human, $childs[$roomIndex]);
            } else {
                $results[$roomIndex] = $human;
            }
        }

        return $results;
    }
}
