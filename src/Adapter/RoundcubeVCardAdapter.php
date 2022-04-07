<?php

namespace OpenXPort\Adapter;

use OpenXPort\Jmap\Contact\ContactInformation;
use Sabre\VObject;
use OpenXPort\Util\AdapterUtil;

class RoundcubeVCardAdapter extends VCardOxpAdapter
{
    public function getAnniversary()
    {
        $vCardAnniversary = $this->vcard->__get("X-ANNIVERSARY");

        if (AdapterUtil::isSetAndNotNull($vCardAnniversary)) {
            // The vCard property X-ANNIVERSARY contains a single string value which represents a date
            // which we obtain via the getParts() method.
            // (getParts() always returns an array, even for single values,
            // that's why in this case we access the value from it with index 0)
            // Then, we use our util method for parsing dates and dates with time and try to parse it.
            // If parsing failed, i.e., the util method returns null, then we return the default value for JMAP
            // which in this case is "0000-00-00"
            $inputDateFormat = "Y-m-d";
            $outputDateFormat = "Y-m-d";
            $jmapAnniversary = AdapterUtil::parseDateTime(
                $vCardAnniversary->getParts()[0],
                $inputDateFormat,
                $outputDateFormat
            );

            if (is_null($jmapAnniversary)) {
                return "0000-00-00";
            }

            return $jmapAnniversary;
        }

        // Return default JMAP value if the supplied data from the vCard is either not set or null
        return "0000-00-00";
    }

    public function setAnniversary($anniversary)
    {
        // Check that the anniversary we receive as value from JMAP is set, not null, not empty and does not equal
        // the string "0000-00-00" (which in JMAP terms denotes that the anniversary date is unknown)
        // Only if all of the checks have passed, go on and create a vCard X-ANNIVERSARY property
        if (
            AdapterUtil::isSetAndNotNull($anniversary)
            && !empty($anniversary)
            && strcmp($anniversary, "0000-00-00") !== 0
        ) {
            $inputDateFormat = "Y-m-d";
            $outputDateFormat = "Y-m-d";

            // Use our util function for parsing the JMAP datetime string and returning a vCard datetime string
            $vCardAnniversary = AdapterUtil::parseDateTime(
                $anniversary,
                $inputDateFormat,
                $outputDateFormat
            );

            // If the datetime parsing was unsuccessful, then the parseDateTime function will have returned null.
            // We can check if $vCardAnniversary is null and if yes, then we don't proceed with writing anniversary data
            if (is_null($vCardAnniversary)) {
                return;
            }

            // If all is good thus far, write the anniversary data in a X-ANNIVERSARY property and set its
            // VALUE parameter to have the value of DATE
            $this->vcard->add(
                'X-ANNIVERSARY',
                $vCardAnniversary,
                [
                    'value' => 'date'
                ]
            );
        }
    }

    public function getDepartment()
    {
        $vCardDepartment = $this->vcard->__get("X-DEPARTMENT");

        if (AdapterUtil::isSetAndNotNull($vCardDepartment) && !empty($vCardDepartment)) {
            // The vCard property X-DEPARTMENT contains a single string value which we simply
            // obtain via the getParts() method and subsequently return
            // (getParts() always returns an array, even for single values,
            // that's why in this case we access the value from it with index 0)
            return $vCardDepartment->getParts()[0];
        }

        return null;
    }

    public function setDepartment($department)
    {
        if (AdapterUtil::isSetAndNotNull($department) && !empty($department)) {
            $this->vcard->add(
                'X-DEPARTMENT',
                $department
            );
        }
    }

    public function getPhones()
    {
        // An array to hold our JMAP phones
        $jmapPhones = [];

        // The phones from vCard
        $vCardPhones = $this->vcard->TEL;

        // Check if the phones from vCard are set, not null and not empty and only then proceed with
        // the transformation to JMAP phones
        if (AdapterUtil::isSetAndNotNull($vCardPhones) && !empty($vCardPhones)) {
            // Since we can have multiple phone entries in vCard, we can iterate through them with foreach
            foreach ($vCardPhones as $vCardPhone) {
                $vCardPhoneValue = $vCardPhone->getParts()[0];

                // If the phone value is not set, is null or is empty, continue iterating and skip this entry
                if (!AdapterUtil::isSetAndNotNull($vCardPhoneValue) || empty($vCardPhoneValue)) {
                    continue;
                }

                // Create a JMAP phone object
                $jmapPhone = new ContactInformation();
                $jmapPhone->setValue($vCardPhoneValue);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                // Obtain the parameter "TYPE" which describes the phone's type
                // (e.g., "home", "work", etc.)
                $vCardPhoneType = $vCardPhone->parameters()["TYPE"];

                // Map the vCard phone type to the JMAP phone type
                switch ($vCardPhoneType->getParts()[0]) {
                    case 'home':
                    case 'home2':
                        $jmapPhone->setType("home");
                        break;

                    case 'work':
                    case 'work2':
                        $jmapPhone->setType("work");
                        break;

                    case 'CELL':
                        $jmapPhone->setType("mobile");
                        break;

                    case 'homefax':
                    case 'workfax':
                        $jmapPhone->setType("fax");
                        break;

                    case 'pager':
                        $jmapPhone->setType("pager");
                        break;

                    case 'main':
                    case 'car':
                    case 'video':
                    case 'assistant':
                    case 'other':
                        $jmapPhone->setType("other");
                        break;

                    default:
                        $jmapPhone->setType("other");
                        break;
                }

                array_push($jmapPhones, $jmapPhone);
            }

            // In case we don't have any JMAP phones, return null
            if (count($jmapPhones) === 0) {
                return null;
            }

            return $jmapPhones;
        }

        // If the vCard phones were not set, null or empty, return null
        return null;
    }

    public function setPhones($phones)
    {
        // Check if the phones that we receive from JMAP are set, not null and not empty
        // and only then proceed with mapping them to vCard phones
        if (AdapterUtil::isSetAndNotNull($phones) && !empty($phones)) {
            foreach ($phones as $phone) {
                // Obtain the JMAP phone's value and if it's unset, null or empty, skip this phone entry
                $phoneValue = $phone->value;
                if (!AdapterUtil::isSetAndNotNull($phoneValue) || empty($phoneValue)) {
                    continue;
                }

                // Obtain the JMAP phone's type and map it accordingly to the corresponding vCard type
                $phoneType = $phone->type;
                $vCardPhoneType = null;

                switch ($phoneType) {
                    case 'home':
                        $vCardPhoneType = 'home';
                        break;

                    case 'work':
                        $vCardPhoneType = 'work';
                        break;

                    case 'mobile':
                        $vCardPhoneType = 'mobile';
                        break;

                    case 'fax':
                        $vCardPhoneType = 'homefax';
                        break;

                    case 'pager':
                        $vCardPhoneType = 'pager';
                        break;

                    case 'other':
                        $vCardPhoneType = 'other';
                        break;

                    default:
                        $vCardPhoneType = 'other';
                        break;
                }

                // Finally, create a vCard TEL property with the phone value and type that we already have
                // and add this created property to our vCard
                $this->vcard->add(
                    'TEL',
                    $phoneValue,
                    [
                        'type' => $vCardPhoneType
                    ]
                );
            }
        }
    }

    public function getWebsites()
    {
        // An array to hold our JMAP websites
        $jmapWebsites = [];

        // The websites from vCard
        $vCardWebsites = $this->vcard->URL;

        // Check if the websites from vCard are set, not null and not empty and only then proceed with
        // the transformation to JMAP websites
        if (AdapterUtil::isSetAndNotNull($vCardWebsites) && !empty($vCardWebsites)) {
            // Since we can have multiple website entries in vCard, we can iterate through them with foreach
            foreach ($vCardWebsites as $vCardWebsite) {
                $vCardWebsiteValue = $vCardWebsite->getParts()[0];

                // If the website value is not set, is null or is empty, continue iterating and skip this entry
                if (!AdapterUtil::isSetAndNotNull($vCardWebsiteValue) || empty($vCardWebsiteValue)) {
                    continue;
                }

                // Create a JMAP website object
                $jmapWebsite = new ContactInformation();
                $jmapWebsite->setValue($vCardWebsiteValue);
                $jmapWebsite->setLabel(null);
                $jmapWebsite->setIsDefault(false);

                // Obtain the parameter "TYPE" which describes the website's type
                // (e.g., "homepage", "work", "blog", etc.)
                $vCardWebsiteType = $vCardWebsite->parameters()["TYPE"];

                // Map the vCard website type to the JMAP website type
                switch ($vCardWebsiteType->getParts()[0]) {
                    case 'homepage':
                    case 'work':
                    case 'blog':
                    case 'profile':
                    case 'other':
                        $jmapWebsite->setType("uri");
                        break;

                    default:
                        $jmapWebsite->setType("other");
                        break;
                }

                array_push($jmapWebsites, $jmapWebsite);
            }

            // In case we don't have any JMAP websites, return null
            if (count($jmapWebsites) === 0) {
                return null;
            }

            return $jmapWebsites;
        }

        // If the vCard websites were not set, null or empty, return null
        return null;
    }

    public function setWebsites($websites)
    {
        // Check if the websites that we receive from JMAP are set, not null and not empty
        // and only then proceed with mapping them to vCard websites
        if (AdapterUtil::isSetAndNotNull($websites) && !empty($websites)) {
            foreach ($websites as $website) {
                // Obtain the JMAP website's value and if it's unset, null or empty, skip this website entry
                $websiteValue = $website->value;
                if (!AdapterUtil::isSetAndNotNull($websiteValue) || empty($websiteValue)) {
                    continue;
                }

                // If the JMAP website's type is 'uri', then save it as a vCard website (as a URL property)
                $websiteType = $website->type;
                if (strcmp($websiteType, 'uri') === 0) {
                    $this->vcard->add(
                        'URL',
                        $websiteValue,
                        [
                            'type' => 'other'
                        ]
                    );
                }
            }
        }
    }

    public function getIm()
    {
        $jmapIms = [];

        // Obtain the different vCard properties that hold IM data
        $vCardAimIm = $this->vcard->__get("X-AIM");
        $vCardIcqIm = $this->vcard->__get("X-ICQ");
        $vCardMsnIm = $this->vcard->__get("X-MSN");
        $vCardYahooIm = $this->vcard->__get("X-YAHOO");
        $vCardJabberIm = $this->vcard->__get("X-JABBER");
        $vCardSkypeIm = $this->vcard->__get("X-SKYPE-USERNAME");

        // Get all AIM IM data and convert it to JMAP IM data
        if (AdapterUtil::isSetAndNotNull($vCardAimIm) && !empty($vCardAimIm)) {
            foreach ($vCardAimIm as $aim) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($aim->getParts()[0]);
                $jmapIm->setLabel('AIM');
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        // Get all ICQ IM data and convert it to JMAP IM data
        if (AdapterUtil::isSetAndNotNull($vCardIcqIm) && !empty($vCardIcqIm)) {
            foreach ($vCardIcqIm as $icq) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($icq->getParts()[0]);
                $jmapIm->setLabel('ICQ');
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        // Get all MSN IM data and convert it to JMAP IM data
        if (AdapterUtil::isSetAndNotNull($vCardMsnIm) && !empty($vCardMsnIm)) {
            foreach ($vCardMsnIm as $msn) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($msn->getParts()[0]);
                $jmapIm->setLabel('MSN');
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        // Get all Yahoo IM data and convert it to JMAP IM data
        if (AdapterUtil::isSetAndNotNull($vCardYahooIm) && !empty($vCardYahooIm)) {
            foreach ($vCardYahooIm as $yahoo) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($yahoo->getParts()[0]);
                $jmapIm->setLabel('Yahoo');
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        // Get all Jabber IM data and convert it to JMAP IM data
        if (AdapterUtil::isSetAndNotNull($vCardJabberIm) && !empty($vCardJabberIm)) {
            foreach ($vCardJabberIm as $jabber) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($jabber->getParts()[0]);
                $jmapIm->setLabel('Jabber');
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        // Get all Skype IM data and convert it to JMAP IM data
        if (AdapterUtil::isSetAndNotNull($vCardSkypeIm) && !empty($vCardSkypeIm)) {
            foreach ($vCardSkypeIm as $skype) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($skype->getParts()[0]);
                $jmapIm->setLabel('Skype');
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        // The code below is currently commented out, since it is originally Roundcube-specific,
        // but the Roundcube behavior needed for it was broken and we didn't need this code.
        // It will most probably be changed to work in a universal way for various webmailers
        // if (!is_null($this->contact['im:other'])) {
        //     foreach ($this->contact['im:other'] as $otherIm) {
        //         $jmapIm = new ContactInformation();
        //         $jmapIm->setType('username');
        //         $jmapIm->setValue($otherIm);
        //         $jmapIm->setLabel('Other');
        //         $jmapIm->setIsDefault(false);

        //         array_push($jmapIms, $jmapIm);
        //     }
        // }

        // In case we don't have any JMAP IM entries, return null
        if (count($jmapIms) === 0) {
            return null;
        }

        return $jmapIms;
    }

    public function setIm($ims)
    {
        // Check if the IMs that we receive from JMAP are set, not null and not empty
        // and only then proceed with mapping them to vCard IMs
        if (AdapterUtil::isSetAndNotNull($ims) && !empty($ims)) {
            foreach ($ims as $im) {
                // Obtain the JMAP IM's value and if it's unset, null or empty, skip this IM entry
                $imValue = $im->value;
                if (!AdapterUtil::isSetAndNotNull($imValue) || empty($imValue)) {
                    continue;
                }

                // If the JMAP IM's type is 'username', then save it as a vCard IM
                $imType = $im->type;
                if (strcmp($imType, 'username') === 0) {
                    // In order to create an exact IM entry, look at the value inside the JMAP IM's label property
                    // This provides info regarding the exact type of the IM (NOTE: THIS IS ROUNDCUBE-SPECIFIC)
                    // (Possibly extract this Roundcube-specific logic for IM out of the generic VCardAdapter?)
                    $imLabel = $im->label;
                    if (AdapterUtil::isSetAndNotNull($imLabel) && !empty($imLabel)) {
                        $vCardImPropType = null;

                        switch ($imLabel) {
                            case 'AIM':
                                $vCardImPropType = 'X-AIM';
                                break;

                            case 'ICQ':
                                $vCardImPropType = 'X-ICQ';
                                break;

                            case 'MSN':
                                $vCardImPropType = 'X-MSN';
                                break;

                            case 'Yahoo':
                                $vCardImPropType = 'X-YAHOO';
                                break;

                            case 'Jabber':
                                $vCardImPropType = 'X-JABBER';
                                break;

                            case 'Skype':
                                $vCardImPropType = 'X-SKYPE-USERNAME';
                                break;

                            default:
                                break;
                        }

                        // If we managed to obtain the specific IM type (e.g. Skype, MSN, etc.) via the JMAP IM's
                        // label property, then create the corresponding IM property and add it to our vCard
                        if (!is_null($vCardImPropType)) {
                            $this->vcard->add(
                                $vCardImPropType,
                                $imValue
                            );
                        }
                    }
                }
            }
        }
    }

    public function getMaidenname()
    {
        $vCardMaidenName = $this->vcard->__get("X-MAIDENNAME");

        if (AdapterUtil::isSetAndNotNull($vCardMaidenName) && !empty($vCardMaidenName)) {
            // The vCard property X-MAIDENNAME contains a single string value which we simply
            // obtain via the getParts() method and subsequently return
            // (getParts() always returns an array, even for single values,
            // that's why in this case we access the value from it with index 0)
            return $vCardMaidenName->getParts()[0];
        }

        return null;
    }

    public function setMaidenname($maidenname)
    {
        if (AdapterUtil::isSetAndNotNull($maidenname) && !empty($maidenname)) {
            $this->vcard->add(
                'X-MAIDENNAME',
                $maidenname
            );
        }
    }

    public function getGender()
    {
        $vCardGender = $this->vcard->__get("X-GENDER");

        if (AdapterUtil::isSetAndNotNull($vCardGender) && !empty($vCardGender)) {
            // The vCard property X-GENDER contains a single string value which we simply
            // obtain via the getParts() method and subsequently return
            // (getParts() always returns an array, even for single values,
            // that's why in this case we access the value from it with index 0)
            return $vCardGender->getParts()[0];
        }

        return null;
    }

    public function setGender($gender)
    {
        if (
            AdapterUtil::isSetAndNotNull($gender)
            && !empty($gender)
            && in_array($gender, array("male", "female")) // Check if the value of $gender is one of the allowed values
        ) {
            $this->vcard->add(
                'X-GENDER',
                $gender
            );
        }
    }

    public function getRelatedTo()
    {
        $jmapRelatedTo = [];

        $vCardManager = $this->vcard->__get("X-MANAGER");
        $vCardAssistant = $this->vcard->__get("X-ASSISTANT");
        $vCardSpouse = $this->vcard->__get("X-SPOUSE");

        if (AdapterUtil::isSetAndNotNull($vCardManager) && !empty($vCardManager)) {
            $jmapRelatedTo[$vCardManager->getParts()[0]] = array("relation" => array("manager" => true));
        }

        if (AdapterUtil::isSetAndNotNull($vCardAssistant) && !empty($vCardAssistant)) {
            $jmapRelatedTo[$vCardAssistant->getParts()[0]] = array("relation" => array("assistant" => true));
        }

        if (AdapterUtil::isSetAndNotNull($vCardSpouse) && !empty($vCardSpouse)) {
            $jmapRelatedTo[$vCardSpouse->getParts()[0]] = array("relation" => array("spouse" => true));
        }

        return $jmapRelatedTo;
    }

    public function setRelatedTo($relatedTo)
    {
        // Directly return from this function if the input is unset, null or empty
        if (!AdapterUtil::isSetNotNullAndNotEmpty($relatedTo)) {
            return;
        }

        // The $relatedTo JMAP property that we receive here is a map of string values to Relation objects
        // We need to take each string value and set it accordingly in our vCard as per the defined relation
        // in the Relation object (e.g., spouse relation, assistant relation, manager relation)
        foreach ($relatedTo as $relatedToString => $relationObject) {
            // Check if the string value is set, not null and not empty and only then proceed with using it
            // as a value to set for a spouse, assistant or manager property in our vCard
            if (AdapterUtil::isSetNotNullAndNotEmpty($relatedToString)) {
                // Obtain the actual relation property from the Relation object and check the type of relation in it
                $relation = $relationObject->relation;

                if (AdapterUtil::isSetNotNullAndNotEmpty($relation)) {
                    $relationType = key($relation);

                    // In case that relationType is unset, null or empty, exit the function,
                    // since we use relationType for the creation and setting of the corresponding
                    // property in vCard and if relationType is not available, then we can't write anything to the vCard
                    if (!AdapterUtil::isSetNotNullAndNotEmpty($relationType)) {
                        return;
                    }

                    // Name of the vCard property to create based on the relation type (e.g., X-MANAGER)
                    $vCardPropName = null;

                    switch ($relationType) {
                        case 'assistant':
                            $vCardPropName = 'X-ASSISTANT';
                            break;

                        case 'manager':
                            $vCardPropName = 'X-MANAGER';
                            break;

                        case 'spouse':
                            $vCardPropName = 'X-SPOUSE';
                            break;

                        default:
                            return;
                            break;
                    }

                    // If the name of the vCard is not null and not unset, then add the respective property to our vCard
                    if (AdapterUtil::isSetAndNotNull($vCardPropName)) {
                        $this->vcard->add($vCardPropName, $relatedToString);
                    }
                }
            }
        }
    }
}
