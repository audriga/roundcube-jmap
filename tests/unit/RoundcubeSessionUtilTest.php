<?php

namespace OpenXPort\Test;

use OpenXPort\Jmap\Core\Account;
use OpenXPort\Jmap\JSContact\ContactsAccountCapability;
use OpenXPort\Util\RoundcubeSessionUtil;
use PHPUnit\Framework\TestCase;

final class RoundcubeSessionUtilTest extends TestCase
{
    /** @var \OpenXPort\Jmap\Core\Session $session */
    private $session;

    /** @var \OpenXPort\Jmap\JSContact\ContactsAccountCapability $contactsCapability */
    private $contactsCapability;

    /** @var \OpenXPort\Jmap\Core\Account $account */
    private $account;

    protected function setUp(): void
    {
        $this->contactsCapability = new ContactsAccountCapability();
    }

    protected function tearDown(): void
    {
        $this->session = null;
        $this->contactsCapability = null;
        $this->account = null;
    }

    public function testCreateSessionWithEmptyAccountData()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Provided account data from Roundcube is empty");
        $this->session = RoundcubeSessionUtil::createSession([]);
    }

    public function testCreateSessionWithoutUsername()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("\"username\" not found in Roundcube account data");
        $this->session = RoundcubeSessionUtil::createSession(['accountId' => 'bla', 'accountCapabilities' => []]);
    }

    public function testCreateSessionWithoutAccountId()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("\"accountId\" not found in Roundcube account data");
        $this->session = RoundcubeSessionUtil::createSession(['username' => 'bla', 'accountCapabilities' => []]);
    }

    public function testCreateSessionWithoutAccountCapabilities()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("\"accountCapabilities\" not found in Roundcube account data");
        $this->session = RoundcubeSessionUtil::createSession(['username' => 'bla', 'accountId' => 'anotherBla']);
    }

    public function testCreateSessionSuccessfully()
    {
        $accountData = [
            'accountId' => 'testAccountId',
            'username' => 'testUsername',
            'accountCapabilities' => [$this->contactsCapability]
        ];

        $this->session = RoundcubeSessionUtil::createSession($accountData);

        $this->account = new Account();
        $this->account->setName($accountData['username']);
        $this->account->setIsPersonal(true);
        $this->account->setIsReadOnly(false);

        $accountCapabilities = array_reduce($accountData['accountCapabilities'], function ($result, $item) {
            $result[$item->getName()] = (object) $item->getCapabilities();
            return $result;
        }, []);

        $this->account->setAccountCapabilities($accountCapabilities);

        $primaryAccounts = array_reduce(array_keys($accountCapabilities), function ($result, $item) use ($accountData) {
            $result[$item] = $accountData['accountId'];
            return $result;
        }, []);

        $this->assertNotNull($this->session);
        $this->assertEquals($this->session->getAccounts(), [
            $accountData['accountId'] => $this->account
        ]);
        $this->assertEquals($this->session->getPrimaryAccounts(), $primaryAccounts);
    }
}
