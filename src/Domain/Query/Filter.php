<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Query;

use InvalidArgumentException;

final readonly class Filter
{
    /**
     * Allowed SQL-like comparison operators. The list mirrors the operator
     * set the repository adapter knows how to translate into a query.
     * Anything outside this set is rejected at the VO boundary to prevent
     * injection of arbitrary operator strings.
     */
    public const ALLOWED_OPERATORS = ['=', '!=', '<', '>', '<=', '>=', 'like', 'in', 'not in'];

    public function __construct(
        public string $field,
        public mixed $value,
        public string $operator = '=',
    ) {
        if (!in_array(strtolower($this->operator), self::ALLOWED_OPERATORS, true)) {
            throw new InvalidArgumentException(sprintf(
                "Filter operator must be one of [%s], got '%s'.",
                implode(', ', self::ALLOWED_OPERATORS),
                $this->operator,
            ));
        }
    }
}
