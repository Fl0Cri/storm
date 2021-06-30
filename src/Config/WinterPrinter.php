<?php

namespace Winter\Storm\Config;

use PhpParser\Node\Expr\ArrayItem;
use PhpParser\PrettyPrinter\Standard;

class WinterPrinter extends Standard
{
    protected function pMaybeMultiline(array $nodes, bool $trailingComma = false)
    {
        if ($this->hasNodeWithComments($nodes) || (isset($nodes[0]) && $nodes[0] instanceof ArrayItem)) {
            return $this->pCommaSeparatedMultiline($nodes, $trailingComma) . $this->nl;
        } else {
            return $this->pCommaSeparated($nodes);
        }
    }

    protected function pComments(array $comments): string
    {
        $formattedComments = [];

        foreach ($comments as $comment) {
            $formattedComments[] = str_replace("\n", $this->nl, $comment->getReformattedText());
        }

        return $this->nl . implode($this->nl, $formattedComments) . $this->nl;
    }
}
