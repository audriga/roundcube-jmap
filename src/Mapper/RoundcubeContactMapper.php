<?php

namespace OpenXPort\Mapper;

use Jmap\Contact\Contact;

class RoundcubeContactMapper extends AbstractMapper
{

    public function mapFromJmap($jmapData, $adapter)
    {
        $map = [];

        foreach ($jmapData as $contact) {
            // TODO weird way of initializing
            $adapter->setContact([]);

            $contactToCreate = reset($contact);
            $creationId = key($jmapData);

            $adapter->setFirstName($contactToCreate->firstName);
            $adapter->setLastName($contactToCreate->lastName);
            $adapter->setPrefix($contactToCreate->prefix);
            $adapter->setSuffix($contactToCreate->suffix);
            $adapter->setNickname($contactToCreate->nickname);
            $adapter->setBirthday($contactToCreate->birthday);
            $adapter->setAnniversary($contactToCreate->anniversary);
            $adapter->setJobTitle($contactToCreate->jobTitle);
            $adapter->setOrganization($contactToCreate->company);
            $adapter->setDepartment($contactToCreate->department);
            $adapter->setNotes($contactToCreate->notes);
            $adapter->setEmails($contactToCreate->emails);
            $adapter->setPhones($contactToCreate->phones);
            $adapter->setWebsites($contactToCreate->online);
            $adapter->setIm($contactToCreate->online);
            $adapter->setAddresses($contactToCreate->addresses);

            array_push($map, array($creationId => $adapter->getContact()));
        }

        return $map;
    }

    public function mapToJmap($data, $adapter)
    {
        $list = [];

        foreach ($data as $c) {
            $adapter->setContact($c);

            $jc = new Contact();

            // TODO ID and UID is missing. Is that OK?
            $jc->setFirstName($adapter->getFirstName());
            $jc->setLastName($adapter->getLastName());
            $jc->setPrefix($adapter->getPrefix());
            $jc->setSuffix($adapter->getSuffix());
            $jc->setNickname($adapter->getNickname());
            $jc->setBirthday($adapter->getBirthday());
            $jc->setAnniversary($adapter->getAnniversary());
            $jc->setJobTitle($adapter->getJobTitle());
            $jc->setCompany($adapter->getOrganization());
            $jc->setDepartment($adapter->getDepartment());
            $jc->setNotes($adapter->getNotes());
            $jc->setEmails($adapter->getEmails());
            $jc->setPhones($adapter->getPhones());
            $jc->setAddresses($adapter->getAddresses());

            $jmapWebsites = $adapter->getWebsites();
            $jmapIms = $adapter->getIm();
            $jmapOnline = array_merge($jmapWebsites, $jmapIms);

            $jc->setOnline($jmapOnline);

            array_push($list, $jc);
        }

        return $list;
    }
}
