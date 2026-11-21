<?php

namespace OGame\GameMessages;

use OGame\GameMessages\Abstracts\ExpeditionGameMessage;

class ExpeditionGainItem extends ExpeditionGameMessage
{
    /**
     * The base key for the message.
     * @var string
     */
    protected static string $baseKey = 'expedition_gain_item';

    /**
     * This controls the number of possible message variations. These should be added to the language files.
     * E.g. if this is 2, then the following message keys should be added to the language files:
     * - t_messages.expedition_gain_item.body.1
     * - t_messages.expedition_gain_item.body.2
     *
     * When increasing this number, make sure to add the english translations for the new message keys.
     *
     * @var int
     */
    protected static int $numberOfVariations = 1;

    /**
     * Kalendrath: names the item won (param item_code, translated when read).
     */
    public function getBody(): string
    {
        $body = parent::getBody();
        $code = $this->message->params['item_code'] ?? null;
        if (is_string($code) && $code !== '') {
            $body .= '<br><br>' . e(__('t_items.message.item_found', ['item' => __('t_items.item.' . $code . '.name')]));
        }

        return $body;
    }
}
