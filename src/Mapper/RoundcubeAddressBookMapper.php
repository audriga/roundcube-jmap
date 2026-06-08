<?php

namespace OpenXPort\Mapper;

use OpenXPort\Jmap\JSContact\AddressBook;

class RoundcubeAddressBookMapper
{
    public function mapToJmap($data, $adapter = null)
    {
        $list = [];
        foreach ($data as $id => $row) {
            $addressBook = new AddressBook();
            $addressBook->setId((string)$id);
            $addressBook->setName($row['name']);
            $addressBook->setIsDefault(false);
            $addressBook->setIsSubscribed(true);
            $list[] = $addressBook;
        }
        return $list;
    }

    public function mapFromJmap($data, $adapter = null)
    {
        $result = [];
        foreach ($data as $creationId => $addressBook) {
            $addressBook = is_object($addressBook) ? (array)$addressBook : $addressBook;
            $result[] = [
                $creationId => [
                    'name' => isset($addressBook['name']) ? $addressBook['name'] : 'New Address Book'
                ]
            ];
        }
        return $result;
    }
}
