<?php

declare(strict_types=1);

namespace Tests\Unit\Scripts;

use CoverageChecker;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

require_once __DIR__ . '/../../../scripts/check-coverage.php';

final class CoverageCheckerTest extends TestCase
{
    private function clover(string $body): SimpleXMLElement
    {
        return new SimpleXMLElement('<?xml version="1.0"?><coverage>' . $body . '</coverage>');
    }

    public function test_collects_files_with_metrics(): void
    {
        $xml = $this->clover(
            '<file name="/abs/src/Foo.php"><metrics statements="10" coveredstatements="9"/></file>'
            . '<file name="/abs/src/Bar.php"><metrics statements="4" coveredstatements="2"/></file>',
        );

        $files = (new CoverageChecker())->collectFileCoverage($xml);

        self::assertCount(2, $files);
        self::assertSame(['statements' => 10, 'covered' => 9], $files['/abs/src/Foo.php']);
        self::assertSame(['statements' => 4, 'covered' => 2], $files['/abs/src/Bar.php']);
    }

    public function test_skips_files_without_metrics(): void
    {
        $xml = $this->clover(
            '<file name="/abs/src/NoMetrics.php"></file>'
            . '<file name="/abs/src/Has.php"><metrics statements="3" coveredstatements="3"/></file>',
        );

        $files = (new CoverageChecker())->collectFileCoverage($xml);

        self::assertArrayNotHasKey('/abs/src/NoMetrics.php', $files);
        self::assertArrayHasKey('/abs/src/Has.php', $files);
    }

    public function test_skips_files_with_zero_statements(): void
    {
        $xml = $this->clover(
            '<file name="/abs/src/Empty.php"><metrics statements="0" coveredstatements="0"/></file>',
        );

        $files = (new CoverageChecker())->collectFileCoverage($xml);

        self::assertSame([], $files);
    }

    public function test_check_returns_ok_when_layer_meets_threshold(): void
    {
        $files = [
            '/abs/src/Domain/Foo.php' => ['statements' => 10, 'covered' => 10],
        ];
        $config = [
            'Domain' => ['path' => 'src/Domain', 'min' => 95.0],
        ];

        $result = (new CoverageChecker())->check($files, $config, '/abs');

        self::assertFalse($result['failed']);
        self::assertCount(1, $result['lines']);
        self::assertStringContainsString('[OK ] Domain', $result['lines'][0]);
        self::assertStringContainsString('100.00%', $result['lines'][0]);
    }

    public function test_check_returns_failed_when_layer_below_threshold(): void
    {
        $files = [
            '/abs/src/Domain/Foo.php' => ['statements' => 10, 'covered' => 5],
        ];
        $config = [
            'Domain' => ['path' => 'src/Domain', 'min' => 95.0],
        ];

        $result = (new CoverageChecker())->check($files, $config, '/abs');

        self::assertTrue($result['failed']);
        self::assertStringContainsString('[FAIL]', $result['lines'][0]);
        self::assertStringContainsString('50.00%', $result['lines'][0]);
    }

    public function test_check_skips_layer_with_no_matching_files(): void
    {
        $files = [
            '/abs/src/Domain/Foo.php' => ['statements' => 10, 'covered' => 10],
        ];
        $config = [
            'Application' => ['path' => 'src/Application', 'min' => 90.0],
        ];

        $result = (new CoverageChecker())->check($files, $config, '/abs');

        self::assertFalse($result['failed']);
        self::assertStringContainsString('[SKIP]', $result['lines'][0]);
    }

    public function test_check_throws_on_malformed_config_entry(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CoverageChecker())->check(
            [],
            ['Bad' => ['path' => 'src/X']], // missing 'min'
            '/abs',
        );
    }
}
