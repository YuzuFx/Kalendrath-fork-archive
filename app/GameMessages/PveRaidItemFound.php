<?php

namespace OGame\GameMessages;

use OGame\GameMessages\Abstracts\GameMessage;

/**
 * Kalendrath: item won by a victorious raid on a PvE camp. Stores the item
 * code (stable), the name is translated when the message is read.
 */
class PveRaidItemFound extends GameMessage
{
    protected function initialize(): void
    {
        $this->key = 'pve_raid_item_found';
        $this->params = ['item_code', 'coordinates'];
        $this->tab = 'fleets';
        $this->subtab = 'combat_reports';
    }

    /**
     * @param array<string, string> $params
     * @return array<string, string>
     */
    protected function checkParams(array $params): array
    {
        $params = parent::checkParams($params);
        $params['item'] = (string) __('t_items.item.' . $params['item_code'] . '.name');

        return $params;
    }
}
