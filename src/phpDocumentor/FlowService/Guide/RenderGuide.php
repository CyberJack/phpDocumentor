<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link https://phpdoc.org
 */

namespace phpDocumentor\FlowService\Guide;

use InvalidArgumentException;
use League\Tactician\CommandBus;
use phpDocumentor\Descriptor\DocumentationSetDescriptor;
use phpDocumentor\Descriptor\GuideSetDescriptor;
use phpDocumentor\Descriptor\ProjectDescriptor;
use phpDocumentor\Dsn;
use phpDocumentor\FileSystem\FileSystemFactory;
use phpDocumentor\FlowService\Transformer;
use phpDocumentor\Guides\Configuration;
use phpDocumentor\Guides\Formats\Format;
use phpDocumentor\Guides\RenderCommand;
use phpDocumentor\Guides\Renderer;
use phpDocumentor\Transformer\Template;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

use function sprintf;

/**
 * @experimental Do not use; this stage is meant as a sandbox / playground to experiment with generating guides.
 */
final class RenderGuide implements Transformer, ProjectDescriptor\WithCustomSettings
{
    public const FEATURE_FLAG = 'guides.enabled';

    /** @var LoggerInterface */
    private $logger;

    /** @var CommandBus */
    private $commandBus;

    /** @var Renderer */
    private $renderer;

    /** @var iterable<Format> */
    private $outputFormats;

    /** @var FileSystemFactory */
    private $fileSystems;

    /**
     * @param iterable<Format> $outputFormats
     */
    public function __construct(
        Renderer $renderer,
        LoggerInterface $logger,
        CommandBus $commandBus,
        FileSystemFactory $fileSystems,
        iterable $outputFormats
    ) {
        $this->logger = $logger;
        $this->commandBus = $commandBus;
        $this->renderer = $renderer;
        $this->outputFormats = $outputFormats;
        $this->fileSystems = $fileSystems;
    }

    public function execute(ProjectDescriptor $project, DocumentationSetDescriptor $documentationSet, Template $template): void
    {
        if (!$documentationSet instanceof GuideSetDescriptor) {
            throw new InvalidArgumentException('Invalid documentation set');
        }

        $this->logger->warning(
            'Generating guides is experimental, no BC guarantees are given, use at your own risk'
        );

        $dsn = $documentationSet->getSource()->dsn();
        $stopwatch = $this->startRenderingSetMessage($dsn);

        $this->renderer->initialize($project, $documentationSet, $template);

        $inputFormat = $documentationSet->getInputFormat();

        $configuration = new Configuration($inputFormat, $this->outputFormats);
        $configuration->setOutputFolder($documentationSet->getOutput());

        $this->commandBus->handle(
            new RenderCommand($documentationSet, $configuration, $this->fileSystems->createDestination($documentationSet))
        );

        $this->completedRenderingSetMessage($stopwatch, $dsn);
    }

    public function getDefaultSettings(): array
    {
        return [self::FEATURE_FLAG => false];
    }

    private function startRenderingSetMessage(Dsn $dsn): Stopwatch
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('guide');
        $this->logger->info('Rendering guide ' . $dsn);

        return $stopwatch;
    }

    private function completedRenderingSetMessage(Stopwatch $stopwatch, Dsn $dsn): void
    {
        $stopwatchEvent = $stopwatch->stop('guide');
        $this->logger->info(
            sprintf(
                'Completed rendering guide %s in %.2fms using %.2f mb memory',
                (string) $dsn,
                $stopwatchEvent->getDuration(),
                $stopwatchEvent->getMemory() / 1024 / 1024
            )
        );
    }
}
