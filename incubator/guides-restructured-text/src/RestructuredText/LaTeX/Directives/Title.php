<?php

declare(strict_types=1);

namespace phpDocumentor\Guides\RestructuredText\LaTeX\Directives;

use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\RawNode;
use phpDocumentor\Guides\RestructuredText\Directives\Directive;
use phpDocumentor\Guides\RestructuredText\MarkupLanguageParser;

/**
 * Add a meta title to the document
 *
 * .. title:: Page title
 */
class Title extends Directive
{
    public function getName(): string
    {
        return 'title';
    }

    /**
     * @param string[] $options
     */
    public function process(
        MarkupLanguageParser $parser,
        ?Node $node,
        string $variable,
        string $data,
        array $options
    ): void {
        $document = $parser->getDocument();

        $document->addHeaderNode(new RawNode('\title{' . $data . '}'));

        if ($node === null) {
            return;
        }

        $document->addNode($node);
    }
}
