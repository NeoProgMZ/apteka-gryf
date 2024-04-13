<?php

/**
 * SOAP requests handler class.
 */

namespace Drupal\soap_endpoint\Client;

use DateTime;
use DateTimeZone;

use XMLWriter;

/**
 * SOAP handler.
 */
class GetOrders
{

    /**
     * Last check date.
     *
     * @var DateTime
     */
    private $lastCheckDate;

    /**
     * XML writer object;
     *
     * @var XMLWriter
     */
    private $xmlWriter;

    /**
     * Basic constructor.
     *
     * @param int    $lastOrderId   Last order id.
     * @param string $lastCheckDate Last check date.
     * @return void
     */
    public function __construct(private int $lastOrderId = 0, string $lastCheckDate = '')
    {
        if (false === empty($lastCheckDate)) {
            $timeZone = new DateTimeZone('Europe/Warsaw');
            $this->lastCheckDate = new DateTime($lastCheckDate, $timeZone);
        }
    }

    /**
     * Perform requested operartions.
     *
     * @return string
     */
    public function process(): string
    {
        $result = '';

        $orders = [
            'order' => [
                'id' => 11,
                'number' => 22233,
                'date' => '2006-07-28T08:12:22',
                'status' => 0,
                'country' => [
                    'id' => 1,
                    'name' => 'Polska',
                    'symbol' => 'PL',
                ],
            ],
        ];

        // @TODO get orders from DB.
        if (false === empty($orders)) {
            $this->xmlWriter = new XMLWriter();
            // @TODO process orders into XML.
            $result = $this->ordersToXml($orders);
        }

        return $result;
    }

    /**
     * Return settings used in this operation.
     *
     * @return array
     */
    public function getSettings(): array
    {
        return [
            'lastOrderId' => $this->lastOrderId,
            'lastCheckDate' => $this->lastCheckDate,
        ];
    }

    /**
     * Transform data array into XML string.
     *
     * @param array $orders Lsit of orders data.
     *
     * @return string
     */
    private function ordersToXml(array $orders): string
    {
        $this->xmlWriter->openMemory();
        $this->xmlWriter->setIndent(true);
        $this->xmlWriter->startDocument('1.0', 'windows-1250');
        $this->xmlWriter->startElement('orders');

        array_walk($orders, [$this, 'arrayRowToXml']);

        $this->xmlWriter->endElement();

        return $this->xmlWriter->outputMemory();
    }

    private function arrayRowToXml(null|string|int|array $value, string $key)
    {
        if (is_array($value) === true) {
            // @TODO recursion.
            $this->xmlWriter->startElement($key);
            array_walk($value, [$this, 'arrayRowToXml']);
            $this->xmlWriter->endElement();
            return;
        }

        $this->xmlWriter->writeElement($key, $value);
    }
}