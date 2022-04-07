<?php

namespace OpenXPort\Adapter;

class RoundcubeIdentityAdapter extends AbstractAdapter
{
    private $identity;

    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * Use this function in order to avoid using a constructor which accepts args,
     * since we need an empty constructor for initialization of this class in the $dataAdapters array (in jmap.php)
     */
    public function setIdentity($identity)
    {
        $this->identity = $identity;
    }

    public function getId()
    {
        $identityId = $this->identity['identity_id'];

        if (!isset($identityId) || empty($identityId)) {
            return null;
        }

        return $identityId;
    }

    public function setId($id)
    {
        if (isset($id) && !empty($id)) {
            $this->identity['identity_id'] = $id;
        }
    }

    public function getName()
    {
        $identityName = $this->identity['name'];
        if (!isset($identityName) || empty($identityName)) {
            return "";
        }

        return $identityName;
    }

    public function setName($name)
    {
        if (isset($name) && !empty($name)) {
            $this->identity['name'] = $name;
        }
    }

    public function getEmail()
    {
        $identityEmail = $this->identity['email'];
        if (!isset($identityEmail) || empty($identityEmail)) {
            return null;
        }

        return $identityEmail;
    }

    public function setEmail($email)
    {
        if (isset($email) && !empty($email)) {
            $this->identity['email'] = $email;
        }
    }

    public function getReplyTo()
    {
        if (is_null($this->identity['reply-to']) || empty($this->identity['reply-to'])) {
            return null;
        }

        $jmapReplyTo = new \OpenXPort\Jmap\Mail\EmailAddress();
        $jmapReplyTo->setEmail($this->identity['reply-to']);

        $jmapReplyToArray = [];
        array_push($jmapReplyToArray, $jmapReplyTo);

        return $jmapReplyToArray;
    }

    public function setReplyTo($replyTo)
    {
        if (isset($replyTo) && !empty($replyTo)) {
            // Since replyTo that we receive is an array, we take its first entry
            // and use its email property for the setting of the reply-to property
            // in Roundcube
            $replyToEmail = $replyTo[0]->email;
            $this->identity['reply-to'] = $replyToEmail;
        }
    }

    public function getBcc()
    {
        if (is_null($this->identity['bcc']) || empty($this->identity['bcc'])) {
            return null;
        }

        $jmapBcc = new \OpenXPort\Jmap\Mail\EmailAddress();
        $jmapBcc->setEmail($this->identity['bcc']);

        $jmapBccArray = [];
        array_push($jmapBccArray, $jmapBcc);

        return $jmapBccArray;
    }

    public function setBcc($bcc)
    {
        if (isset($bcc) && !empty($bcc)) {
            // Since bcc that we receive is an array, we take its first entry
            // and use its email property for the setting of the bcc property
            // in Roundcube
            $bccEmail = $bcc[0]->email;
            $this->identity['bcc'] = $bccEmail;
        }
    }

    public function getTextSignature()
    {
        if ($this->identity['html_signature'] == 1) {
            $nonHtmlSignature = strip_tags($this->identity['signature']);
            return $nonHtmlSignature;
        } else {
            return $this->identity['signature'];
        }
    }

    public function setTextSignature($textSignature)
    {
        if (isset($textSignature) && !empty($textSignature)) {
            $this->identity['signature'] = $textSignature;
        }
    }

    public function getHtmlSignature()
    {
        if ($this->identity['html_signature'] == 1) {
            return $this->identity['signature'];
        } else {
            return "";
        }
    }

    public function setHtmlSignature($htmlSignature)
    {
        if (isset($htmlSignature) && !empty($htmlSignature)) {
            $this->identity['signature'] = $htmlSignature;
            $this->identity['html_signature'] = 1;
        }
    }
}
