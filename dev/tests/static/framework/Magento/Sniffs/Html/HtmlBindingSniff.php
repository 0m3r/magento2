<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Sniffs\Html;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Sniffing improper HTML bindings.
 */
class HtmlBindingSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        return [T_INLINE_HTML];
    }

    /**
     * @inheritDoc
     *
     * Find HTML data bindings and check variables used.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if ($stackPtr === 0) {
            $html = $phpcsFile->getTokensAsString($stackPtr, count($phpcsFile->getTokens()));
            $dom = new \DOMDocument();
            try {
                // phpcs:disable Generic.PHP.NoSilencedErrors
                @$dom->loadHTML($html);
                $loaded = true;
            } catch (\Throwable $exception) {
                //Invalid HTML, skipping
                $loaded = false;
            }
            if ($loaded) {
                $domXpath = new \DOMXPath($dom);
                $dataBindAttributes = $domXpath->query('//@*[name() = "data-bind"]');
                foreach ($dataBindAttributes as $dataBindAttribute) {
                    $knockoutBinding = $dataBindAttribute->nodeValue;
                    preg_match('/^(.+\s*?)?html\s*?\:\s*?([a-z0-9\.\(\)\_]+)/ims', $knockoutBinding, $htmlBinding);
                    if ($htmlBinding && !preg_match('/UnsanitizedHtml[\(\)]*?$/', $htmlBinding[2])) {
                        $phpcsFile->addError(
                            'Variables/functions used for HTML binding must have UnsanitizedHtml suffix'
                            .' - "' .$htmlBinding[2] .'" doesn\'t,' .PHP_EOL
                            .'consider using text binding if the value is supposed to be text',
                            null,
                            'UIComponentTemplate.KnockoutBinding.HtmlSuffix'
                        );
                    }
                }
            }
        }
    }
}
