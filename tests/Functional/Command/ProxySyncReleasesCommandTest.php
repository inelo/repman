<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Command;

use Buddy\Repman\Command\ProxySyncReleasesCommand;
use Buddy\Repman\Service\Cache\InMemoryCache;
use Buddy\Repman\Service\Dist\Storage\FileStorage;
use Buddy\Repman\Service\Downloader;
use Buddy\Repman\Service\Proxy\MetadataProvider\CacheableMetadataProvider;
use Buddy\Repman\Service\Proxy\ProxyFactory;
use Buddy\Repman\Service\Proxy\ProxyRegister;
use Buddy\Repman\Tests\Doubles\FakeDownloader;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;
use Munus\Control\Option;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Tester\CommandTester;

final class ProxySyncReleasesCommandTest extends FunctionalTestCase
{
    private string $basePath = __DIR__.'/../../Resources';
    private FilesystemAdapter $cache;

    public function testSyncReleases(): void
    {
        $newDist = $this->basePath.'/packagist.org/dist/buddy-works/repman/1.2.3.0_5e77ad71826b9411cb873c0947a7d541d822dff1.zip';
        @unlink($newDist);

        $feed = (string) file_get_contents($this->basePath.'/packagist.org/feed/releases.rss');

        // cache miss (no pubDate)
        $command = $this->prepareCommand($feed);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertTrue(file_exists($newDist));
        @unlink($newDist);

        // cache hit (pubDate is set)
        $command = $this->prepareCommand($feed, true);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertFalse(file_exists($newDist));
    }

    public function testParsingError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to parse RSS feed');

        $command = $this->prepareCommand('invalid xml');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
    }

    private function prepareCommand(string $feed, bool $fromCache = false): ProxySyncReleasesCommand
    {
        if (!$fromCache) {
            $this->cache()->delete('pub_date');
        }

        $feedDownloader = $this->createMock(Downloader::class);
        $feedDownloader->method('getContents')->willReturn(Option::of($feed));

        $storageDownloader = $this->createMock(Downloader::class);
        $storageDownloader->method('getContents')->willReturn(Option::of('test'));

        return new ProxySyncReleasesCommand(
            new ProxyRegister(
                new ProxyFactory(
                    new CacheableMetadataProvider(new FakeDownloader(), new InMemoryCache()),
                    new FileStorage($this->basePath, $storageDownloader)
                )
            ),
            $feedDownloader,
            $this->cache()
        );
    }

    private function cache(): FilesystemAdapter
    {
        return $this->cache = $this->cache ?? new FilesystemAdapter('test', 0, self::$kernel->getCacheDir());
    }
}
