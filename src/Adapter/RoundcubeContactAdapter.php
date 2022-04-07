<?php

namespace OpenXPort\Adapter;

use OpenXPort\Jmap\Contact\ContactInformation;
use OpenXPort\Jmap\Contact\Address;
use OpenXPort\Jmap\Contact\File;
use JeroenDesloovere\VCard\VCard;
use JeroenDesloovere\VCard\VCardParser;

class RoundcubeContactAdapter extends AbstractAdapter
{
    private $contact;

    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Use this function in order to avoid using a constructor which accepts args,
     * since we need an empty constructor for initialization of this class in the $dataAdapters array (in jmap.php)
     */
    public function setContact($contact)
    {
        $this->contact = $contact;
    }

    public function getId()
    {
        return $this->contact['ID'];
    }

    public function getFirstName()
    {
        return $this->contact['firstname'];
    }

    public function setFirstName($firstName)
    {
        $this->contact['firstname'] = $firstName;
    }

    public function getLastName()
    {
        return $this->contact['surname'];
    }

    public function setLastName($lastName)
    {
        $this->contact['surname'] = $lastName;
    }

    public function getPrefix()
    {
        return $this->contact['prefix'];
    }

    public function setPrefix($prefix)
    {
        $this->contact['prefix'] = $prefix;
    }

    public function getSuffix()
    {
        return $this->contact['suffix'];
    }

    public function setSuffix($suffix)
    {
        $this->contact['suffix'] = $suffix;
    }

    public function getNickname()
    {
        return $this->contact['nickname'];
    }

    public function setNickname($nickname)
    {
        $this->contact['nickname'] = $nickname;
    }

    public function getBirthday()
    {
        return $this->contact['birthday'][0];
    }

    public function setBirthday($birthday)
    {
        $this->contact['birthday'][0] = $birthday;
    }

    public function getAnniversary()
    {
        return $this->contact['anniversary'][0];
    }

    public function setAnniversary($anniversary)
    {
        $this->contact['anniversary'][0] = $anniversary;
    }

    public function getJobTitle()
    {
        return $this->contact['jobtitle'][0];
    }

    public function setJobTitle($jobTitle)
    {
        $this->contact['jobtitle'][0] = $jobTitle;
    }

    public function getOrganization()
    {
        return $this->contact['organization'];
    }

    public function setOrganization($organization)
    {
        $this->contact['organization'] = $organization;
    }

    public function getDepartment()
    {
        return $this->contact['department'][0];
    }

    public function setDepartment($department)
    {
        $this->contact['department'][0] = $department;
    }

    public function getNotes()
    {
        return $this->contact['notes'][0];
    }

    public function setNotes($notes)
    {
        $this->contact['notes'][0] = $notes;
    }

    public function getEmails()
    {
        $jmapEmails = [];

        if (!is_null($this->contact['email:home'])) {
            foreach ($this->contact['email:home'] as $homeEmail) {
                $jmapEmail = new ContactInformation();
                $jmapEmail->setType('personal');
                $jmapEmail->setValue($homeEmail);
                $jmapEmail->setLabel(null);
                $jmapEmail->setIsDefault(false);

                array_push($jmapEmails, $jmapEmail);
            }
        }

        if (!is_null($this->contact['email:work'])) {
            foreach ($this->contact['email:work'] as $workEmail) {
                $jmapEmail = new ContactInformation();
                $jmapEmail->setType('work');
                $jmapEmail->setValue($workEmail);
                $jmapEmail->setLabel(null);
                $jmapEmail->setIsDefault(false);

                array_push($jmapEmails, $jmapEmail);
            }
        }

        if (!is_null($this->contact['email:other'])) {
            foreach ($this->contact['email:other'] as $otherEmail) {
                $jmapEmail = new ContactInformation();
                $jmapEmail->setType('other');
                $jmapEmail->setValue($otherEmail);
                $jmapEmail->setLabel(null);
                $jmapEmail->setIsDefault(false);

                array_push($jmapEmails, $jmapEmail);
            }
        }

        return $jmapEmails;
    }

    public function setEmails($emails)
    {
        if (is_null($emails) || empty($emails)) {
            return;
        }

        $homeEmails = [];
        $workEmails = [];
        $otherEmails = [];

        foreach ($emails as $jmapEmail) {
            if (strcmp($jmapEmail->type, 'personal') === 0) {
                array_push($homeEmails, $jmapEmail->value);
            }

            if (strcmp($jmapEmail->type, 'work') === 0) {
                array_push($workEmails, $jmapEmail->value);
            }

            if (strcmp($jmapEmail->type, 'other') === 0) {
                array_push($otherEmails, $jmapEmail->value);
            }
        }

        $this->contact['email:home'] = $homeEmails;
        $this->contact['email:work'] = $workEmails;
        $this->contact['email:other'] = $otherEmails;
    }

    public function getPhones()
    {
        $jmapPhones = [];

        if (!is_null($this->contact['phone:home'])) {
            foreach ($this->contact['phone:home'] as $homePhone) {
                $jmapPhone = new ContactInformation();
                $jmapPhone->setType('home');
                $jmapPhone->setValue($homePhone);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapPhone);
            }
        }

        if (!is_null($this->contact['phone:home2'])) {
            foreach ($this->contact['phone:home2'] as $homePhone) {
                $jmapPhone = new ContactInformation();
                $jmapPhone->setType('home');
                $jmapPhone->setValue($homePhone);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapPhone);
            }
        }

        if (!is_null($this->contact['phone:work'])) {
            foreach ($this->contact['phone:work'] as $workPhone) {
                $jmapPhone = new ContactInformation();
                $jmapPhone->setType('work');
                $jmapPhone->setValue($workPhone);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapPhone);
            }
        }

        if (!is_null($this->contact['phone:work2'])) {
            foreach ($this->contact['phone:work2'] as $workPhone) {
                $jmapPhone = new ContactInformation();
                $jmapPhone->setType('work');
                $jmapPhone->setValue($workPhone);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapPhone);
            }
        }

        if (!is_null($this->contact['phone:mobile'])) {
            foreach ($this->contact['phone:mobile'] as $mobilePhone) {
                $jmapPhone = new ContactInformation();
                $jmapPhone->setType('mobile');
                $jmapPhone->setValue($mobilePhone);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapPhone);
            }
        }

        if (!is_null($this->contact['phone:main'])) {
            foreach ($this->contact['phone:main'] as $mainPhone) {
                $jmapPhone = new ContactInformation();
                $jmapPhone->setType('other');
                $jmapPhone->setValue($mainPhone);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapPhone);
            }
        }

        if (!is_null($this->contact['phone:homefax'])) {
            foreach ($this->contact['phone:homefax'] as $homeFax) {
                $jmapPhone = new ContactInformation();
                $jmapPhone->setType('fax');
                $jmapPhone->setValue($homeFax);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapPhone);
            }
        }

        if (!is_null($this->contact['phone:workfax'])) {
            foreach ($this->contact['phone:workfax'] as $workFax) {
                $jmapPhone = new ContactInformation();
                $jmapPhone->setType('fax');
                $jmapPhone->setValue($workFax);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapPhone);
            }
        }

        if (!is_null($this->contact['phone:car'])) {
            foreach ($this->contact['phone:car'] as $carPhone) {
                $jmapPhone = new ContactInformation();
                $jmapPhone->setType('other');
                $jmapPhone->setValue($carPhone);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapPhone);
            }
        }

        if (!is_null($this->contact['phone:pager'])) {
            foreach ($this->contact['phone:pager'] as $pager) {
                $jmapPhone = new ContactInformation();
                $jmapPhone->setType('pager');
                $jmapPhone->setValue($pager);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapPhone);
            }
        }

        if (!is_null($this->contact['phone:video'])) {
            foreach ($this->contact['phone:video'] as $videoPhone) {
                $jmapPhone = new ContactInformation();
                $jmapPhone->setType('other');
                $jmapPhone->setValue($videoPhone);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapPhone);
            }
        }

        if (!is_null($this->contact['phone:assistant'])) {
            foreach ($this->contact['phone:assistant'] as $assistantPhone) {
                $jmapPhone = new ContactInformation();
                $jmapPhone->setType('other');
                $jmapPhone->setValue($assistantPhone);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapPhone);
            }
        }

        if (!is_null($this->contact['phone:other'])) {
            foreach ($this->contact['phone:other'] as $otherPhone) {
                $jmapPhone = new ContactInformation();
                $jmapPhone->setType('other');
                $jmapPhone->setValue($otherPhone);
                $jmapPhone->setLabel(null);
                $jmapPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapPhone);
            }
        }

        return $jmapPhones;
    }

    public function setPhones($phones)
    {
        if (is_null($phones) || empty($phones)) {
            return;
        }

        $homePhones = [];
        $workPhones = [];
        $mobilePhones = [];
        $faxes = [];
        $pagers = [];
        $otherPhones = [];

        foreach ($phones as $jmapPhone) {
            if (strcmp($jmapPhone->type, 'home') === 0) {
                array_push($homePhones, $jmapPhone->value);
            }

            if (strcmp($jmapPhone->type, 'work') === 0) {
                array_push($workPhones, $jmapPhone->value);
            }

            if (strcmp($jmapPhone->type, 'mobile') === 0) {
                array_push($mobilePhones, $jmapPhone->value);
            }

            if (strcmp($jmapPhone->type, 'fax') === 0) {
                array_push($faxes, $jmapPhone->value);
            }

            if (strcmp($jmapPhone->type, 'pager') === 0) {
                array_push($pagers, $jmapPhone->value);
            }

            if (strcmp($jmapPhone->type, 'other') === 0) {
                array_push($otherPhones, $jmapPhone->value);
            }
        }

        $this->contact['phone:home'] = $homePhones;
        $this->contact['phone:work'] = $workPhones;
        $this->contact['phone:mobile'] = $mobilePhones;
        $this->contact['phone:workfax'] = $faxes;
        $this->contact['phone:pager'] = $pagers;
        $this->contact['phone:other'] = $otherPhones;
    }

    public function getWebsites()
    {
        $jmapWebsites = [];

        if (!is_null($this->contact['website:homepage'])) {
            foreach ($this->contact['website:homepage'] as $homepage) {
                $jmapWebsite = new ContactInformation();
                $jmapWebsite->setType('uri');
                $jmapWebsite->setValue($homepage);
                $jmapWebsite->setLabel('homepage');
                $jmapWebsite->setIsDefault(false);

                array_push($jmapWebsites, $jmapWebsite);
            }
        }

        if (!is_null($this->contact['website:work'])) {
            foreach ($this->contact['website:work'] as $workWebsite) {
                $jmapWebsite = new ContactInformation();
                $jmapWebsite->setType('uri');
                $jmapWebsite->setValue($workWebsite);
                $jmapWebsite->setLabel('work');
                $jmapWebsite->setIsDefault(false);

                array_push($jmapWebsites, $jmapWebsite);
            }
        }

        if (!is_null($this->contact['website:blog'])) {
            foreach ($this->contact['website:blog'] as $blogWebsite) {
                $jmapWebsite = new ContactInformation();
                $jmapWebsite->setType('uri');
                $jmapWebsite->setValue($blogWebsite);
                $jmapWebsite->setLabel('blog');
                $jmapWebsite->setIsDefault(false);

                array_push($jmapWebsites, $jmapWebsite);
            }
        }

        if (!is_null($this->contact['website:profile'])) {
            foreach ($this->contact['website:profile'] as $profileWebsite) {
                $jmapWebsite = new ContactInformation();
                $jmapWebsite->setType('uri');
                $jmapWebsite->setValue($profileWebsite);
                $jmapWebsite->setLabel('profile');
                $jmapWebsite->setIsDefault(false);

                array_push($jmapWebsites, $jmapWebsite);
            }
        }

        if (!is_null($this->contact['website:other'])) {
            foreach ($this->contact['website:other'] as $otherWebsite) {
                $jmapWebsite = new ContactInformation();
                $jmapWebsite->setType('uri');
                $jmapWebsite->setValue($otherWebsite);
                $jmapWebsite->setLabel('other');
                $jmapWebsite->setIsDefault(false);

                array_push($jmapWebsites, $jmapWebsite);
            }
        }

        return $jmapWebsites;
    }

    public function setWebsites($websites)
    {
        if (is_null($websites) || empty($websites)) {
            return;
        }

        $otherWebsites = [];

        foreach ($websites as $jmapWebsite) {
            if (strcmp($jmapWebsite->type, 'uri') === 0) {
                array_push($otherWebsites, $jmapWebsite->value);
            }
        }

        $this->contact['website:other'] = $otherWebsites;
    }

    public function getIm()
    {
        $jmapIms = [];

        if (!is_null($this->contact['im:aim'])) {
            foreach ($this->contact['im:aim'] as $aim) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($aim);
                $jmapIm->setLabel('AIM');
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        if (!is_null($this->contact['im:icq'])) {
            foreach ($this->contact['im:icq'] as $icq) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($icq);
                $jmapIm->setLabel('ICQ');
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        if (!is_null($this->contact['im:msn'])) {
            foreach ($this->contact['im:msn'] as $msn) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($msn);
                $jmapIm->setLabel('MSN');
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        if (!is_null($this->contact['im:yahoo'])) {
            foreach ($this->contact['im:yahoo'] as $yahoo) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($yahoo);
                $jmapIm->setLabel('Yahoo');
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        if (!is_null($this->contact['im:jabber'])) {
            foreach ($this->contact['im:jabber'] as $jabber) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($jabber);
                $jmapIm->setLabel('Jabber');
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        if (!is_null($this->contact['im:skype'])) {
            foreach ($this->contact['im:skype'] as $skype) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($skype);
                $jmapIm->setLabel('Skype');
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        if (!is_null($this->contact['im:other'])) {
            foreach ($this->contact['im:other'] as $otherIm) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($otherIm);
                $jmapIm->setLabel('Other');
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        return $jmapIms;
    }

    public function setIm($ims)
    {
        if (is_null($ims) || empty($ims)) {
            return;
        }

        $aimIms = [];
        $icqIms = [];
        $msnIms = [];
        $yahooIms = [];
        $jabberIms = [];
        $skypeIms = [];
        $otherIms = [];

        foreach ($ims as $jmapIm) {
            if (strcmp($jmapIm->type, 'username') === 0) {
                if (strcmp($jmapIm->label, 'AIM') === 0) {
                    array_push($aimIms, $jmapIm->value);
                }

                if (strcmp($jmapIm->label, 'ICQ') === 0) {
                    array_push($icqIms, $jmapIm->value);
                }

                if (strcmp($jmapIm->label, 'MSN') === 0) {
                    array_push($msnIms, $jmapIm->value);
                }

                if (strcmp($jmapIm->label, 'Yahoo') === 0) {
                    array_push($yahooIms, $jmapIm->value);
                }

                if (strcmp($jmapIm->label, 'Jabber') === 0) {
                    array_push($jabberIms, $jmapIm->value);
                }

                if (strcmp($jmapIm->label, 'Skype') === 0) {
                    array_push($skypeIms, $jmapIm->value);
                }

                if (in_array($jmapIm->label, ['AIM', 'ICQ', 'MSN', 'Yahoo', 'Jabber', 'Skype']) === false) {
                    array_push($otherIms, $jmapIm->value);
                }
            }
        }

        $this->contact['im:aim'] = $aimIms;
        $this->contact['im:icq'] = $icqIms;
        $this->contact['im:msn'] = $msnIms;
        $this->contact['im:yahoo'] = $yahooIms;
        $this->contact['im:jabber'] = $jabberIms;
        $this->contact['im:skype'] = $skypeIms;
        $this->contact['im:other'] = $otherIms;
    }

    public function getAddresses()
    {
        $jmapAddresses = [];

        if (!is_null($this->contact['address:home'])) {
            foreach ($this->contact['address:home'] as $homeAddress) {
                $jmapAddress = new Address();
                $jmapAddress->setType('home');
                $jmapAddress->setLabel(null);
                $jmapAddress->setStreet($homeAddress['street']);
                $jmapAddress->setLocality($homeAddress['locality']);
                $jmapAddress->setRegion($homeAddress['region']);
                $jmapAddress->setPostcode($homeAddress['zipcode']);
                $jmapAddress->setCountry($homeAddress['country']);
                $jmapAddress->setIsDefault(false);

                array_push($jmapAddresses, $jmapAddress);
            }
        }

        if (!is_null($this->contact['address:work'])) {
            foreach ($this->contact['address:work'] as $workAddress) {
                $jmapAddress = new Address();
                $jmapAddress->setType('work');
                $jmapAddress->setLabel(null);
                $jmapAddress->setStreet($workAddress['street']);
                $jmapAddress->setLocality($workAddress['locality']);
                $jmapAddress->setRegion($workAddress['region']);
                $jmapAddress->setPostcode($workAddress['zipcode']);
                $jmapAddress->setCountry($workAddress['country']);
                $jmapAddress->setIsDefault(false);

                array_push($jmapAddresses, $jmapAddress);
            }
        }

        if (!is_null($this->contact['address:other'])) {
            foreach ($this->contact['address:other'] as $otherAddress) {
                $jmapAddress = new Address();
                $jmapAddress->setType('other');
                $jmapAddress->setLabel(null);
                $jmapAddress->setStreet($otherAddress['street']);
                $jmapAddress->setLocality($otherAddress['locality']);
                $jmapAddress->setRegion($otherAddress['region']);
                $jmapAddress->setPostcode($otherAddress['zipcode']);
                $jmapAddress->setCountry($otherAddress['country']);
                $jmapAddress->setIsDefault(false);

                array_push($jmapAddresses, $jmapAddress);
            }
        }

        return $jmapAddresses;
    }

    public function setAddresses($addresses)
    {
        if (is_null($addresses) || empty($addresses)) {
            return;
        }

        $homeAddresses = [];
        $workAddresses = [];
        $otherAddresses = [];

        foreach ($addresses as $jmapAddress) {
            if (strcmp($jmapAddress->type, 'home') === 0) {
                $homeAddress = [];
                $homeAddress['street'] = $jmapAddress->street;
                $homeAddress['locality'] = $jmapAddress->locality;
                $homeAddress['region'] = $jmapAddress->region;
                $homeAddress['zipcode'] = $jmapAddress->postcode;
                $homeAddress['country'] = $jmapAddress->country;

                array_push($homeAddresses, $homeAddress);
            }

            if (strcmp($jmapAddress->type, 'work') === 0) {
                $workAddress = [];
                $workAddress['street'] = $jmapAddress->street;
                $workAddress['locality'] = $jmapAddress->locality;
                $workAddress['region'] = $jmapAddress->region;
                $workAddress['zipcode'] = $jmapAddress->postcode;
                $workAddress['country'] = $jmapAddress->country;

                array_push($workAddresses, $workAddress);
            }

            if (in_array($jmapAddress->type, ['home', 'work']) === false) {
                $otherAddress = [];
                $otherAddress['street'] = $jmapAddress->street;
                $otherAddress['locality'] = $jmapAddress->locality;
                $otherAddress['region'] = $jmapAddress->region;
                $otherAddress['zipcode'] = $jmapAddress->postcode;
                $otherAddress['country'] = $jmapAddress->country;

                array_push($otherAddresses, $otherAddress);
            }
        }

        $this->contact['address:home'] = $homeAddresses;
        $this->contact['address:work'] = $workAddresses;
        $this->contact['address:other'] = $otherAddresses;
    }

    public function getMiddlename()
    {
        return $this->contact['middlename'];
    }

    public function getDisplayname()
    {
        return $this->contact['name'];
    }

    public function getMaidenname()
    {
        return $this->contact['maidenname'][0];
    }

    public function getGender()
    {
        return $this->contact['gender'][0];
    }

    public function getRelatedTo()
    {
        $jmapRelatedTo = [];

        $manager = $this->contact['manager'][0];
        $assistant = $this->contact['assistant'][0];
        $spouse = $this->contact['spouse'][0];

        if (isset($manager)) {
            $jmapRelatedTo["$manager"] = array("relation" => array("manager" => true));
        }

        if (isset($assistant)) {
            $jmapRelatedTo["$assistant"] = array("relation" => array("assistant" => true));
        }

        if (isset($spouse)) {
            $jmapRelatedTo["$spouse"] = array("relation" => array("spouse" => true));
        }

        return $jmapRelatedTo;
    }

    public function getRole()
    {
        // TODO: Implement me (currently no 'role' property in Roundcube)
    }

    public function getAvatar()
    {
        // Use vCard lib to read base64 value of the contact image
        $vCardString = $this->contact['vcard'];
        $parser = new VCardParser($vCardString);
        $vCard = $parser->getCardAtIndex(0);

        $jmapAvatar = null;

        /**
         * The 'rawPhoto' property of the parsed vCard holds the image data.
         * However, the vCard lib doesn't put a base64-encoded value there,
         * but rather the binary value of the image that was base64-encoded in the vCard string.
         * That's why we call `base64_encode` on the binary value in this propery,
         * in order to get a base64 value.
         */
        if (isset($vCard->rawPhoto)) {
            $base64Avatar = base64_encode($vCard->rawPhoto);
            $jmapAvatar = new File();
            $jmapAvatar->setBase64($base64Avatar);
        }


        return $jmapAvatar;
    }
}
