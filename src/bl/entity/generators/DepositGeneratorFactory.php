<?php

namespace bl\entity\generators;

use app\client\ComfyUIClient;
use bl\entity\generators\deposit\AbstractDepositGenerator;
use bl\entity\generators\deposit;
use models\DepositType;

/**
 * Factory for creating deposit generators
 */
class DepositGeneratorFactory
{
    /** @var ComfyUIClient */
    private $fluxClient;

    /** @var string */
    private $basePath;

    /** @var array Cached generator instances */
    private $generators = [];

    /** @var array Map of image_url to generator class */
    private static $generatorMap = [
        'ore_iron' => deposit\IronOreGenerator::class,
        'ore_copper' => deposit\CopperOreGenerator::class,
        'ore_aluminum' => deposit\AluminumOreGenerator::class,
        'ore_titanium' => deposit\TitaniumOreGenerator::class,
        'ore_silver' => deposit\SilverOreGenerator::class,
        'ore_gold' => deposit\GoldOreGenerator::class,
    ];

    public function __construct(?ComfyUIClient $fluxClient = null, ?string $basePath = null)
    {
        $this->fluxClient = $fluxClient;
        $this->basePath = $basePath;
    }

    /**
     * Get generator for deposit type
     * @param DepositType|string $depositOrImageUrl
     * @return AbstractDepositGenerator|null
     */
    public function getGenerator($depositOrImageUrl): ?AbstractDepositGenerator
    {
        $imageUrl = $depositOrImageUrl instanceof DepositType
            ? $depositOrImageUrl->image_url
            : $depositOrImageUrl;

        if (isset($this->generators[$imageUrl])) {
            return $this->generators[$imageUrl];
        }

        $generatorClass = self::$generatorMap[$imageUrl] ?? null;

        if ($generatorClass === null) {
            return null;
        }

        $generator = new $generatorClass($this->fluxClient, $this->basePath);
        $this->generators[$imageUrl] = $generator;

        return $generator;
    }

    /**
     * Check if generator exists for image_url
     */
    public function hasGenerator(string $imageUrl): bool
    {
        return isset(self::$generatorMap[$imageUrl]);
    }

    /**
     * Get all registered image_urls
     */
    public function getRegisteredImageUrls(): array
    {
        return array_keys(self::$generatorMap);
    }

    /**
     * Register a new generator
     */
    public static function register(string $imageUrl, string $generatorClass): void
    {
        self::$generatorMap[$imageUrl] = $generatorClass;
    }
}
