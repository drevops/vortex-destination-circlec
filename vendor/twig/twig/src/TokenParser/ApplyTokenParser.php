<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\TokenParser;

use Twig\Node\Expression\Variable\LocalVariable;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\PrintNode;
use Twig\Node\SetNode;
use Twig\Token;

/**
 * Applies filters on a section of a template.
 *
 *   {% apply upper %}
 *      This text becomes uppercase
 *   {% endapply %}
 *
 * @internal
 */
final class ApplyTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $ref = new LocalVariable(null, $lineno);
        $filter = $this->parser->getExpressionParser()->parseFilterExpressionRaw($ref);

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideApplyEnd'], true);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new Nodes([
            new SetNode(true, $ref, $body, $lineno),
            new PrintNode($filter, $lineno),
        ], $lineno);
    }

    public function decideApplyEnd(Token $token): bool
    {
        return $token->test('endapply');
    }

    public function getTag(): string
    {
        return 'apply';
    }
}
