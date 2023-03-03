<?php

namespace OpenXPort\Util;

use OpenXPort\Jmap\Core\Account;
use OpenXPort\Jmap\Core\Session;

class RoundcubeSessionUtil extends SessionUtil
{
    public static function createSession($accountData)
    {
        if (!isset($accountData) || empty($accountData)) {
            throw new \Exception("Provided account data from Roundcube is empty");
        }

        if (!isset($accountData['username'])) {
            throw new \Exception("\"username\" not found in Roundcube account data");
        }

        if (!isset($accountData['accountId'])) {
            throw new \Exception("\"accountId\" not found in Roundcube account data");
        }

        if (!isset($accountData['accountCapabilities'])) {
            throw new \Exception("\"accountCapabilities\" not found in Roundcube account data");
        }

        $accountId = $accountData['accountId'];
        $username = $accountData['username'];
        $accountCapabilities = array_reduce($accountData['accountCapabilities'], function ($result, $item) {
            $result[$item->getName()] = (object) $item->getCapabilities();
            return $result;
        }, []);

        $sessionAccount = new Account();
        $sessionAccount->setName($username);
        
        // Assuming true and false as default values for isPersonal and isReadOnly for now
        $sessionAccount->setIsPersonal(true);
        $sessionAccount->setIsReadOnly(false);
        
        $sessionAccount->setAccountCapabilities($accountCapabilities);

        $accounts = [$accountId => $sessionAccount];

        // We construct "primaryAccounts" of the JMAP session object by taking the array keys of
        // the account capabilities (i.e., the account capability names) and mapping them all to accountId
        $primaryAccounts = array_reduce(array_keys($accountCapabilities), function ($result, $item) use ($accountId) {
            $result[$item] = $accountId;
            return $result;
        }, []);
        
        return new Session($accounts, $primaryAccounts, $username);
    }
}
