<?php

namespace PedroTroller\CS\Fixer\CodingStyle;

use PedroTroller\CS\Fixer\AbstractFixer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class LineBreakBetweenStatementsFixer extends AbstractFixer
{
    /*
     * {@inheritdoc}
     */
    public function getSampleCode()
    {
        return <<<'PHP'
<?php

namespace Project\TheNamespace;

class TheClass
{
    /**
     * @return null
     */
    public function fun() {
        do {
            // ...
        } while (true);
        foreach (['foo', 'bar'] as $str) {
            // ...
        }
        if (true === false) {
            // ...
        }


        while (true) {
            // ...
        }
    }
}
PHP;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentation()
    {
        return 'Transform multiline docblocks with only one comment into a singleline docblock.';
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        for ($index = 0; $index < $tokens->count() - 2; ++$index) {
            $token = $tokens[$index];

            if (false === $token->equals('}')) {
                continue;
            }

            $spaceIndex = $index + 1;

            if (false === $tokens[$spaceIndex]->isGivenKind(T_WHITESPACE)) {
                continue;
            }

            $statement = $tokens[$index + 2];

            switch ($statement->getId()) {
                // If it's a while, isolate the case of do {} while ();
                case T_WHILE:
                    $semicolon = $tokens->getNextTokenOfKind($index + 1, [';']);
                    $break     = false;
                    if (null !== $semicolon) {
                        $break = true;
                        for ($next = $index + 1; $next < $semicolon; ++$next) {
                            if ($tokens[$next]->equals('{')) {
                                $break = false;
                            }
                        }
                    }

                    if (true === $break) {
                        $nextSpace = $tokens->getNextTokenOfKind((int) $semicolon, [[T_WHITESPACE]]);
                        if (null !== $nextSpace) {
                            $spaceIndex = $nextSpace;
                        }
                    }
                    // no break
                case T_IF:
                case T_DO:
                case T_FOREACH:
                case T_SWITCH:
                case T_FOR:
                    $tokens[$spaceIndex] = new Token([T_WHITESPACE, $this->ensureNumberOfBreaks($tokens[$spaceIndex]->getContent())]);
            }
        }
    }

    private function ensureNumberOfBreaks($whitespace)
    {
        $parts = explode("\n", $whitespace);

        while (3 < count($parts)) {
            array_shift($parts);
        }

        while (3 > count($parts)) {
            array_unshift($parts, '');
        }

        return implode("\n", $parts);
    }
}
