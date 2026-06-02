<?php

namespace OpenXPort\Mapper;

use OpenXPort\Jmap\JSContact\ContactCard;
use OpenXPort\Jmap\JSContact\Name;
use OpenXPort\Jmap\JSContact\NameComponent;
use OpenXPort\Jmap\JSContact\Note;

class RoundcubeContactCardMapper extends RoundcubeJSContactVCardMapper
{
    public function mapFromJmap($jmapData, $adapter)
    {
        foreach ($jmapData as $creationId => $card) {
            if (!($card instanceof ContactCard)) {
                continue;
            }

            $this->fixNotesField($card);
            $this->fixNameComponents($card);
        }

        return parent::mapFromJmap($jmapData, $adapter);
    }

    /**
     * ContactCard::fromJson() always treats the JSON "notes" key as a simple string,
     * but RFC 9553 defines notes as Id[Note]. This converts whatever arrived in the
     * string slot into real noteObjects so setNotes() can write NOTE to the vCard.
     */
    private function fixNotesField(ContactCard $card)
    {
        if ($card->getNoteObjects() !== null) {
            return;
        }

        $notes = $card->getNotes();
        if ($notes === null) {
            return;
        }

        // RFC 9553 clients send `"notes": {"n1": {"note": "..."}}` which fromJson()
        // stashes as a stdClass/array in the string slot. Convert to Note objects.
        if (is_object($notes) || is_array($notes)) {
            $map = is_array($notes) ? $notes : (array) $notes;
            $converted = [];
            foreach ($map as $id => $item) {
                $item = is_object($item) ? $item : (object) $item;
                if (isset($item->note) && is_string($item->note) && $item->note !== '') {
                    $note = new Note();
                    $note->setNote($item->note);
                    $converted[(string) $id] = $note;
                }
            }
            if (!empty($converted)) {
                $card->setNoteObjects($converted);
            }
            return;
        }

        // Plain string notes (legacy callers): wrap in a single Note object.
        if (is_string($notes) && $notes !== '') {
            $note = new Note();
            $note->setNote($notes);
            $card->setNoteObjects(['n1' => $note]);
        }
    }

    /**
     * setName() skips writing N when components is empty, causing Sabre to
     * auto-add the required-but-empty N:;;;; for vCard 3.0 compliance.
     * When only name.full is present, derive given/surname by splitting on the
     * last space so N carries meaningful data.
     */
    private function fixNameComponents(ContactCard $card)
    {
        $name = $card->getName();
        if (!($name instanceof Name)) {
            return;
        }

        $components = $name->getComponents();
        if (!empty($components)) {
            return;
        }

        $full = trim((string) $name->getFull());
        if ($full === '') {
            return;
        }

        $lastSpace = strrpos($full, ' ');
        if ($lastSpace !== false) {
            $given   = trim(substr($full, 0, $lastSpace));
            $surname = trim(substr($full, $lastSpace + 1));
        } else {
            $given   = '';
            $surname = $full;
        }

        $comps = [];
        if ($given !== '') {
            $c = new NameComponent();
            $c->setKind('given');
            $c->setValue($given);
            $comps[] = $c;
        }
        if ($surname !== '') {
            $c = new NameComponent();
            $c->setKind('surname');
            $c->setValue($surname);
            $comps[] = $c;
        }

        if (!empty($comps)) {
            $name->setComponents($comps);
        }
    }
}
