<?php

declare(strict_types=1);

namespace Hotelbook\Object\Method\Search;

use Hotelbook\Connector\ConnectorInterface;
use Hotelbook\Method\Former\Search as SearchFormer;
use Hotelbook\ResultProceeder;
use SimpleXMLElement;

/**
 * Class AsyncSearch
 * @package App\Hotelbook\Object\Method\Search
 */
class AsyncSearch
{
    /**
     * @var ConnectorInterface
     */
    protected $connector;

    /**
     * @var AsyncSearchParams
     */
    protected $params;

    /**
     * @var
     */
    protected $response;

    /**
     * @var
     */
    protected $former;


    /**
     * @var
     */
    protected $extraData;

    /**
     * AsyncSearch constructor.
     * @param ConnectorInterface $connector
     * @param AsyncSearchParams $params
     */
    public function __construct(
        ConnectorInterface $connector,
        AsyncSearchParams $params,
        $extraData
    )
    {
        $this->connector = $connector;
        $this->extraData = $extraData;
        $this->setParams($params);
        $this->setDefaultResponse();

        $this->former = new SearchFormer();
    }

    /**
     * Param Setter
     * @param AsyncSearchParams $params
     */
    protected function setParams(AsyncSearchParams $params)
    {
        $this->params = $params;
    }

    protected function setDefaultResponse()
    {
        $this->setResponse(
            $this->getDefaultResponse()
        );
    }

    /**
     * Response Setter
     * @param $response
     */
    protected function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return null
     */
    protected function getDefaultResponse()
    {
        return null;
    }

    /**
     * @return bool
     */
    public function isCompleted()
    {
        return
            (!($this->isEmpty()
                ||
                (
                    isset($this->response->Hotels)
                    &&
                    (string)$this->response->Hotels->attributes()['searchingIsCompleted'] !== 'true'
                )))
            ||
            $this->hasErrors();
    }

    /**
     * @return bool
     */
    protected function isEmpty()
    {
        return $this->response === null;
    }

    /**
     * @return bool
     */
    protected function hasErrors()
    {
        return !$this->isEmpty() && !empty($this->getErrors($this->response));
    }

    /**
     * @param SimpleXMLElement $element
     * @return array
     */
    protected function getErrors(SimpleXMLElement $element)
    {
        $errors = [];
        if (isset($element->Errors)) {
            foreach ($element->Errors->Error as $err) {
                $err = current($err);
                $errors[] = [
                    'id' => (string)$err['code'],
                    'desc' => (string)$err['description'],
                ];
            }
        }

        return $errors;
    }

    /**
     * @return SearchResult
     */
    public function search()
    {
        $response = $this->connector->request(
            'GET',
            'hotel_search_async',
            null,
            [
                'query' => [
                    'search_id' => $this->params->getSearchId(),
                    'limit_results' => $this->params->getLimit(),
                    'from_result_id' => $this->params->getOffset()
                ]
            ]
        );

        sleep($this->params->getPause());

        $responseDom = dom_import_simplexml($response);
        $extraDataDom = dom_import_simplexml($this->extraData);
        $responseDom->appendChild($responseDom->ownerDocument->importNode($extraDataDom, true));

        $this->setResponse($response);

        $values = [];
        $errors = $this->getErrors($response);
        if (empty($errors) && $this->hasValues()) {
            $values = $this->form($response);
        }

        return new ResultProceeder($values, $errors);
    }

    /**
     * @return bool
     */
    protected function hasValues()
    {
        return (int)$this->response->Hotels->attributes()['totalResults'] !== 0;
    }

    /**
     * Former
     * @param SimpleXMLElement $response
     * @return array|mixed
     */
    protected function form(SimpleXMLElement $response)
    {
        return $this->former->form($response);
    }
}
