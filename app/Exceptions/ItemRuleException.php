<?php

namespace OGame\Exceptions;

use RuntimeException;

/**
 * An item rule was broken (item not available, inventory full...). The
 * message is resolved from the t_items.error.* translation keys, so the API
 * layer can display it as is.
 */
class ItemRuleException extends RuntimeException
{
    public static function because(string $key): self
    {
        return new self(__('t_items.error.' . $key));
    }
}
