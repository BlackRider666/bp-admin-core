<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;

/**
 * Builds a field-keyed validation rules array from an EntityDefinition.
 */
final class RuleBuilder
{
    /**
     * @return array<string, array<string>>
     */
    public static function fromDefinition(EntityDefinitionContract $definition): array
    {
        $rules = [];
        foreach ($definition->fields() as $field) {
            $fieldRules = $field->rules();
            if (!empty($fieldRules)) {
                $rules[$field->name()] = $fieldRules;
            }
        }
        return $rules;
    }
}
