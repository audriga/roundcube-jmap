<?php

namespace OpenXPort\Test\VCard;

use PHPUnit\Framework\TestCase;
use Sabre\VObject;

final class VCardTest extends TestCase
{
    /**
     * @var VCard
     */
    protected $vCard = null;

    /**
     * @var VCard
     */
    protected $vCardWithSpecialChars = null;

    /**
     * @var RoundcubeVCardAdapter
     */
    protected $adapter = null;

    /**
     * @var RoundcubeVCardMapper
     */
    protected $mapper = null;

    public function setUp(): void
    {
        $this->vCard = VObject\Reader::read(
            fopen(__DIR__ . '/../data/rc-vcard.vcf', 'r')
        );

        $this->vCardWithSpecialChars = VObject\Reader::read(
            fopen(__DIR__ . '/../data/vcard-special-chars.vcf', 'r')
        );

        $this->adapter = new \OpenXPort\Adapter\RoundcubeVCardAdapter();
        $this->mapper = new \OpenXPort\Mapper\RoundcubeVCardMapper();
    }

    public function tearDown(): void
    {
        $this->vCard = null;
        $this->vCardWithSpecialChars = null;
        $this->adapter = null;
        $this->mapper = null;
    }

    public function testRoundcubeVCardRoundtrip()
    {
        // Construct an array with vCard to pass to our mapper
        $vCardData = array("1" => $this->vCard->serialize());

        // Convert the vCard data to JMAP data via our mapper
        // (We need to type cast the JMAP contact object, resulting from the call to mapToJmap below,
        // to an stdClass object. This is due to the assumption that we always need an object of stdClass
        // that represents the JMAP Contact in the call to mapFromJmap below.)
        $jmapContacts = (object)(array)$this->mapper->mapToJmap($vCardData, $this->adapter)[0];
        $jmapData = array("c1" => $jmapContacts);

        // Transform the JMAP data from above back to vCard data
        $resultingVCardData = $this->mapper->mapFromJmap($jmapData, $this->adapter);

        // The actual vCard string can be obtained by taking the first element of
        // the first element of $resultingVCardData
        // ($resultingVCardData is an array, containing a second array, thus the nested access)
        $resultingVCardString = reset($resultingVCardData)["c1"];

        // Parse the vCard string to a vCard object with Sabre VObject
        $resultingVCard = \Sabre\VObject\Reader::read($resultingVCardString);

        // Obtain the ADR vCard property of the original vCard and of the vCard, resulting from the roundtrip
        $originalVCardAddress = $this->vCard->ADR->getParts();
        $resultingVCardAddress = $resultingVCard->ADR->getParts();

        // Compare post office box part of ADR
        $this->assertEquals($originalVCardAddress[0], $resultingVCardAddress[0]);

        // Compare extended address part of ADR
        $this->assertEquals($originalVCardAddress[1], $resultingVCardAddress[1]);

        // Compare street address part of ADR
        $this->assertEquals($originalVCardAddress[2], $resultingVCardAddress[2]);

        // Compare locality part of ADR
        $this->assertEquals($originalVCardAddress[3], $resultingVCardAddress[3]);

        // Compare region part of ADR
        $this->assertEquals($originalVCardAddress[4], $resultingVCardAddress[4]);

        // Compare postal code part of ADR
        $this->assertEquals($originalVCardAddress[5], $resultingVCardAddress[5]);

        // Compare country name part of ADR
        $this->assertEquals($originalVCardAddress[6], $resultingVCardAddress[6]);
    }

    public function testRoundcubeVCardSpecialCharsRoundtrip()
    {
        // Construct an array with vCard to pass to our mapper
        $vCardWithSpecialCharsData = array("1" => $this->vCardWithSpecialChars->serialize());

        // Convert the vCard data to JMAP data via our mapper
        // (We need to type cast the JMAP contact object, resulting from the call to mapToJmap below,
        // to an stdClass object. This is due to the assumption that we always need an object of stdClass
        // that represents the JMAP Contact in the call to mapFromJmap below.)
        $jmapContacts = $this->mapper->mapToJmap($vCardWithSpecialCharsData, $this->adapter)[0];

        // Convert the JMAP contact to an instance of stdClass via jsonSerialize()
        $jmapContacts = $jmapContacts->jsonSerialize();

        $jmapWithSpecialCharsData = array("c1" => $jmapContacts);

        // Transform the JMAP data from above back to vCard data
        $resultingVCardWithSpecialCharsData = $this->mapper->mapFromJmap($jmapWithSpecialCharsData, $this->adapter);

        // The actual vCard string can be obtained by taking the first element of
        // the first element of $resultingVCardWithSpecialCharsData
        // ($resultingVCardWithSpecialCharsData is an array, containing a second array, thus the nested access)
        $resultingVCardWithSpecialCharsString = reset($resultingVCardWithSpecialCharsData)["c1"];

        // Parse the vCard string to a vCard object with Sabre VObject
        $resultingVCardWithSpecialChars = \Sabre\VObject\Reader::read($resultingVCardWithSpecialCharsString);

        // Obtain the NOTE vCard property of the original vCard and of the vCard, resulting from the roundtrip
        $originalVCardNote = $this->vCardWithSpecialChars->NOTE->getRawMimeDirValue();
        $resultingVCardNote = $resultingVCardWithSpecialChars->NOTE->getRawMimeDirValue();

        // Compare original and after-roundtrip NOTEs with special character (\;)
        $this->assertEquals($originalVCardNote, $resultingVCardNote);


        // Similarly to NOTE, check for other special character (\,) in TITLE
        $originalVCardTitle = $this->vCardWithSpecialChars->TITLE->getRawMimeDirValue();
        $resultingVCardTitle = $resultingVCardWithSpecialChars->TITLE->getRawMimeDirValue();

        $this->assertEquals($originalVCardTitle, $resultingVCardTitle);
    }
}
