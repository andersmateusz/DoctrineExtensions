<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Tests\Mapping\Fixture\Yaml\Category;
use Gedmo\Tests\Mapping\Fixture\Yaml\LoggableComposite;
use Gedmo\Tests\Mapping\Fixture\Yaml\LoggableCompositeRelation;

/**
 * These are mapping tests for tree extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class LoggableORMMappingTest extends ORMMappingTestCase
{
    public const YAML_CATEGORY = Category::class;
    public const COMPOSITE = LoggableComposite::class;
    public const COMPOSITE_RELATION = LoggableCompositeRelation::class;

    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->getBasicConfiguration();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $yamlDriver = new YamlDriver(__DIR__.'/Driver/Yaml');

        $chain = new MappingDriverChain();
        $chain->addDriver($yamlDriver, 'Gedmo\Tests\Mapping\Fixture\Yaml');
        $chain->addDriver($annotationDriver, 'Gedmo\Tests\Mapping\Fixture');

        $config->setMetadataDriverImpl($chain);

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $evm = new EventManager();
        $loggableListener = new LoggableListener();
        $loggableListener->setCacheItemPool($this->cache);
        $evm->addEventSubscriber($loggableListener);
        $this->em = EntityManager::create($conn, $config, $evm);
    }

    public function testLoggableMapping(): void
    {
        $meta = $this->em->getClassMetadata(self::YAML_CATEGORY);
        $cacheId = ExtensionMetadataFactory::getCacheId(self::YAML_CATEGORY, 'Gedmo\Loggable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);
        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);
    }

    public function testLoggableCompositeMapping(): void
    {
        $meta = $this->em->getClassMetadata(self::COMPOSITE);

        static::assertIsArray($meta->identifier);
        static::assertCount(2, $meta->identifier);

        $cacheId = ExtensionMetadataFactory::getCacheId(self::COMPOSITE, 'Gedmo\Loggable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);
    }

    public function testLoggableCompositeRelationMapping(): void
    {
        $meta = $this->em->getClassMetadata(self::COMPOSITE_RELATION);

        static::assertIsArray($meta->identifier);
        static::assertCount(2, $meta->identifier);

        $cacheId = ExtensionMetadataFactory::getCacheId(self::COMPOSITE_RELATION, 'Gedmo\Loggable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);
    }
}
