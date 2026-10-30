<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ports;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/** A35/A38 — new core ports, checked by reflection (no impls in core). */
final class PortsContractsTest extends TestCase
{
    private const NS = 'BlackParadise\\CoreAdmin\\Domain\\Contracts\\';

    public function test_write_scope_contract_declares_run_callable_returning_mixed(): void
    {
        $fqcn = self::NS . 'Persistence\\WriteScopeContract';
        self::assertTrue(interface_exists($fqcn), "$fqcn must exist.");

        $m = new ReflectionMethod($fqcn, 'run');
        $params = $m->getParameters();

        self::assertCount(1, $params);
        self::assertSame('callable', (string) $params[0]->getType());
        self::assertSame('mixed', (string) $m->getReturnType());
    }

    public function test_action_resolver_contract_declares_resolve_class_string_returning_action(): void
    {
        $fqcn = self::NS . 'Action\\ActionResolverContract';
        self::assertTrue(interface_exists($fqcn), "$fqcn must exist.");

        $m = new ReflectionMethod($fqcn, 'resolve');
        $params = $m->getParameters();
        $ret = $m->getReturnType();

        self::assertCount(1, $params);
        self::assertSame('string', (string) $params[0]->getType());
        self::assertInstanceOf(ReflectionNamedType::class, $ret);
        self::assertSame(self::NS . 'Action\\ActionContract', $ret->getName());
    }

    public function test_download_contract_declares_filename_and_mime_type_strings(): void
    {
        $fqcn = self::NS . 'Action\\DownloadContract';
        self::assertTrue(interface_exists($fqcn), "$fqcn must exist.");

        self::assertSame('string', (string) (new ReflectionMethod($fqcn, 'filename'))->getReturnType());
        self::assertSame('string', (string) (new ReflectionMethod($fqcn, 'mimeType'))->getReturnType());
    }

    public function test_embedded_child_writer_contract_mirrors_write_all_signature(): void
    {
        $fqcn = self::NS . 'Persistence\\EmbeddedChildWriterContract';
        self::assertTrue(interface_exists($fqcn), "$fqcn must exist.");

        $m = new ReflectionMethod($fqcn, 'writeAll');
        $params = $m->getParameters();

        self::assertCount(3, $params);
        self::assertSame(self::NS . 'EntityDefinition\\EntityDefinitionContract', (string) $params[0]->getType());
        self::assertSame(self::NS . 'Entity\\EntityRecordContract', (string) $params[1]->getType());
        self::assertSame('array', (string) $params[2]->getType());
        self::assertSame('defer', $params[2]->getName());
        self::assertSame('void', (string) $m->getReturnType());
    }

    public function test_embedded_child_writer_contract_has_only_write_all(): void
    {
        $fqcn = self::NS . 'Persistence\\EmbeddedChildWriterContract';
        self::assertTrue(interface_exists($fqcn), "$fqcn must exist.");

        $names = array_map(static fn(ReflectionMethod $m): string => $m->getName(), (new ReflectionClass($fqcn))->getMethods());

        self::assertSame(['writeAll'], $names);
    }

    public function test_owned_deletes_collector_contract_mirrors_collect_signature(): void
    {
        $fqcn = self::NS . 'Persistence\\OwnedDeletesCollectorContract';
        self::assertTrue(interface_exists($fqcn), "$fqcn must exist.");

        $m = new ReflectionMethod($fqcn, 'collect');
        $params = $m->getParameters();

        self::assertCount(2, $params);
        self::assertSame(self::NS . 'EntityDefinition\\EntityDefinitionContract', (string) $params[0]->getType());
        self::assertSame(self::NS . 'Entity\\EntityRecordContract', (string) $params[1]->getType());
        self::assertSame('array', (string) $m->getReturnType());
    }

    public function test_owned_deletes_collector_contract_documents_def_and_key_entity_key_shape(): void
    {
        $fqcn = self::NS . 'Persistence\\OwnedDeletesCollectorContract';
        self::assertTrue(interface_exists($fqcn), "$fqcn must exist.");

        $doc = (string) (new ReflectionMethod($fqcn, 'collect'))->getDocComment();

        self::assertStringContainsString('EntityDefinitionContract', $doc);
        self::assertStringContainsString('EntityKey', $doc);
        self::assertMatchesRegularExpression('/def\s*:/', $doc);
        self::assertMatchesRegularExpression('/key\s*:/', $doc);
    }
}
