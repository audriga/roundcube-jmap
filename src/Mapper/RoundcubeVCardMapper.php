<?php

namespace OpenXPort\Mapper;

use OpenXPort\Jmap\Contact\Contact;

class RoundcubeVCardMapper extends VCardMapper
{
    public function mapFromJmap($jmapData, $adapter)
    {
        $map = [];

        foreach ($jmapData as $creationId => $contact) {
            $adapter->setName(
                $contact->lastName,
                $contact->firstName,
                $contact->middlename,
                $contact->prefix,
                $contact->suffix
            );
            $adapter->setNickname($contact->nickname);
            $adapter->setBirthday($contact->birthday);
            $adapter->setAnniversary($contact->anniversary);
            $adapter->setJobTitle($contact->jobTitle);
            $adapter->setOrganization($contact->company);
            $adapter->setDepartment($contact->department);
            $adapter->setNotes($contact->notes);
            $adapter->setEmails($contact->emails);
            $adapter->setPhones($contact->phones);
            $adapter->setWebsites($contact->online);
            $adapter->setIm($contact->online);
            $adapter->setAddresses($contact->addresses);

            $adapter->setDisplayname($contact->displayname);
            $adapter->setMaidenname($contact->maidenname);
            $adapter->setGender($contact->gender);
            $adapter->setRelatedTo($contact->relatedTo);
            $adapter->setAvatar($contact->avatar);

            array_push($map, array($creationId => $adapter->getContact()));
        }

        return $map;
    }

    public function mapToJmap($data, $adapter)
    {
        $list = [];

        foreach ($data as $contactId => $contactVCard) {
            $adapter->setContact($contactVCard);

            $jc = new Contact();

            $jc->setId($contactId);
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

            // Mappings for additional properties (see https://web.audriga.com/mantis/view.php?id=5071)
            $jc->setMiddlename($adapter->getMiddlename());
            $jc->setDisplayname($adapter->getDisplayname());
            $jc->setMaidenname($adapter->getMaidenname());
            $jc->setGender($adapter->getGender());
            $jc->setRelatedTo($adapter->getRelatedTo());
            $jc->setAvatar($adapter->getAvatar());

            $jmapWebsites = $adapter->getWebsites();
            $jmapIms = $adapter->getIm();

            $jmapOnline = array_merge((array) $jmapWebsites, (array) $jmapIms);

            $jc->setOnline($jmapOnline);

            array_push($list, $jc);
        }

        return $list;
    }
}
