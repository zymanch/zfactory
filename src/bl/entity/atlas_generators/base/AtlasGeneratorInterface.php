<?php

namespace bl\entity\atlas_generators\base;

/**
 * Interface for atlas generators
 * Generates texture atlases from GD image resources (NO file operations)
 *
 * Atlas generators combine multiple sprite states/frames into single atlas texture.
 * They work entirely in memory with GD resources - caller handles file I/O.
 */
interface AtlasGeneratorInterface
{
    /**
     * Generate atlas as GD resource
     * @return resource GD image resource of the generated atlas
     */
    public function generate();

    /**
     * Get atlas dimensions
     * @return array{width: int, height: int} Atlas dimensions in pixels
     */
    public function getDimensions(): array;
}
