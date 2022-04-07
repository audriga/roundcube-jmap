<?php

namespace OpenXPort\Mapper;

use OpenXPort\Jmap\Mail\Identity;

class RoundcubeIdentityMapper extends AbstractMapper
{
    public function mapFromJmap($jmapData, $adapter)
    {
        $map = [];

        foreach ($jmapData as $creationId => $identity) {
            $adapter->setIdentity([]);

            $adapter->setName($identity->name);
            $adapter->setEmail($identity->email);
            $adapter->setReplyTo($identity->replyTo);
            $adapter->setBcc($identity->bcc);
            $adapter->setTextSignature($identity->textSignature);
            $adapter->setHtmlSignature($identity->htmlSignature);

            array_push($map, array($creationId => $adapter->getIdentity()));
        }

        return $map;
    }

    public function mapToJmap($data, $adapter)
    {
        $list = [];

        foreach ($data as $i) {
            $adapter->setIdentity($i);

            $ji = new Identity();

            $ji->setId($adapter->getId());
            $ji->setName($adapter->getName());
            $ji->setEmail($adapter->getEmail());
            $ji->setReplyTo($adapter->getReplyTo());
            $ji->setBcc($adapter->getBcc());
            $ji->setTextSignature($adapter->getTextSignature());
            $ji->setHtmlSignature($adapter->getHtmlSignature());

            array_push($list, $ji);
        }

        return $list;
    }
}
