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
            throw new \Exception("Username not found in Roundcube account data");
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
        // the account capabilities (i.e., the account capability names) and map them all to accountId
        $primaryAccounts = array_map(function ($element) use ($accountId) {
            return [$element, $accountId];
        }, array_keys($accountCapabilities));
        
        return new Session($accounts, $primaryAccounts, $username);
    }
}
