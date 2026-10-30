<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Action;

use BlackParadise\CoreAdmin\Domain\Action\ActionContext;
use BlackParadise\CoreAdmin\Domain\Action\ActionResult;
use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionResolverContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;
use BlackParadise\CoreAdmin\Domain\Contracts\LocaleProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Persistence\WriteScopeContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Validation\ValidationProviderContract;
use BlackParadise\CoreAdmin\Domain\Events\ActionExecuted;
use BlackParadise\CoreAdmin\Domain\Exceptions\ActionNotFoundException;
use BlackParadise\CoreAdmin\Domain\Exceptions\InvalidActionScopeException;
use BlackParadise\CoreAdmin\Domain\Exceptions\InvalidDefinitionException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;

/**
 * Runs a named entity action: find -> authorize (once) -> scope check ->
 * input validation -> handle() inside one write scope -> ActionExecuted after commit.
 * Not gated by entity capabilities (actions work on read-only entities).
 */
final readonly class ExecuteActionUseCase
{
    public function __construct(
        private ActionResolverContract $actionResolver,
        private AuthorizationProviderContract $authorizationProvider,
        private ValidationProviderContract $validator,
        private LocaleProviderContract $localeProvider,
        private WriteScopeContract $writeScope,
        private EventDispatcherContract $dispatcher,
    ) {}

    /**
     * @throws ActionNotFoundException
     * @throws UnauthorizedException
     * @throws InvalidActionScopeException
     * @throws ValidationException
     */
    public function execute(EntityDefinitionContract $definition, string $actionName, ActionContext $context): ActionResult
    {
        $action = $this->findAction($definition, $actionName);

        $ability = $action->permission() ?? 'action.' . $actionName;
        if (!$this->authorizationProvider->can($ability, $definition)) {
            throw new UnauthorizedException($definition->name(), $ability);
        }

        if (!in_array($context->scope, $action->scopes(), true)) {
            throw new InvalidActionScopeException($actionName, $context->scope);
        }

        $this->validator->validate($context->input, $this->rulesOf($action));

        /** @var ActionResult $result */
        $result = $this->writeScope->run(function () use ($action, $context, $definition, $actionName): ActionResult {
            $result = $action->handle($context);

            $this->dispatcher->dispatch(new ActionExecuted(
                $definition->name(),
                $actionName,
                $context->scope,
                $context->keys,
                $result,
            ));

            return $result;
        });

        return $result;
    }

    private function findAction(EntityDefinitionContract $definition, string $actionName): ActionContract
    {
        foreach ($definition->actions() as $actionClass) {
            try {
                $action = $this->actionResolver->resolve($actionClass);
            } catch (InvalidDefinitionException $e) {
                throw new InvalidDefinitionException(
                    sprintf('Definition "%s" has an invalid action %s: %s', $definition->name(), $actionClass, $e->getMessage()),
                    0,
                    $e,
                );
            }
            if ($action->name() === $actionName) {
                return $action;
            }
        }

        throw new ActionNotFoundException($definition->name(), $actionName);
    }

    /** @return array<string, array<string>> */
    private function rulesOf(ActionContract $action): array
    {
        $locales = $this->localeProvider->availableLocales();
        $rules = [];
        foreach ($action->fields() as $field) {
            foreach ($field->expandRules($locales, 'create') as $key => $fieldRules) {
                if ($fieldRules !== []) {
                    $rules[$key] = $fieldRules;
                }
            }
        }

        return $rules;
    }
}
