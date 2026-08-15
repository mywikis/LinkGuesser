<?php
namespace MediaWiki\Extension\LinkGuesser;

use Category;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use Title;

class LinkGuesserHooks {
    public static function onHtmlPageLinkRendererEnd(
        LinkRenderer $linkRenderer,
        LinkTarget $target,
        $isKnown, // change to bool $isKnown when possible
        &$text,
        &$attribs,
        &$ret
    ) {
        // If it's not broken, ignore
        if ( $isKnown ) {
            return true;
        }

        // Start to inspect LinkTarget object
        // If it's external, it's outside our scope, ignore
        if ( $target->isExternal() ) {
            return true;
        }

        // For an internal link, get its namespace and DB key (e.g. Monsoon in Event:Monsoon)
        $intendedTargetNamespace = $target->getNamespace();
        $intendedTargetPageKey = $target->getDBkey();

        // A category page may have no description page (so MediaWiki considers
        // it "broken") while still having pages categorized under it. Such a
        // category is worth visiting, so leave the link pointing at it.
        if ( $intendedTargetNamespace === NS_CATEGORY ) {
            $categoryTitle = Title::makeTitleSafe(
                NS_CATEGORY,
                $intendedTargetPageKey
            );

            if ( $categoryTitle !== null && self::categoryHasMembers( $categoryTitle ) ) {
                $attribs['href'] = $categoryTitle->getLinkURL();

                // The link isn't broken from the reader's perspective, so
                // don't style or describe it as such.
                if ( isset( $attribs['class'] ) ) {
                    $classes = array_diff(
                        preg_split( '/\s+/', $attribs['class'], -1, PREG_SPLIT_NO_EMPTY ),
                        [ 'new' ]
                    );

                    if ( $classes === [] ) {
                        unset( $attribs['class'] );
                    } else {
                        $attribs['class'] = implode( ' ', $classes );
                    }
                }

                // LinkRenderer::makeBrokenLink() sets a "(page does not exist)"
                // tooltip, which no longer applies.
                $attribs['title'] = $categoryTitle->getPrefixedText();

                return true;
            }
        }

        // Swap link to point to Special:ResolveLink
        $resolveLinkSpecialPageTitle = Title::newFromText(
            'ResolveLink',
            NS_SPECIAL
        );
        $attribs['href'] = $resolveLinkSpecialPageTitle->getLinkURL(
            [
                'ns' => $intendedTargetNamespace,
                'pg' => $intendedTargetPageKey
            ]
        );
        
        return true;
    }

    /**
     * Whether a category has pages categorized under it.
     *
     * Results are memoized per request, because this hook can be called many
     * times for the same category (e.g. on Special:WantedCategories) and each
     * Category object issues its own uncached database query.
     *
     * @param Title $categoryTitle
     * @return bool
     */
    private static function categoryHasMembers(
        Title $categoryTitle
    ): bool {
        static $cache = [];

        $key = $categoryTitle->getDBkey();

        if ( !array_key_exists( $key, $cache ) ) {
            $category = Category::newFromTitle( $categoryTitle );
            $cache[$key] = $category->getPageCount() > 0;
        }

        return $cache[$key];
    }
}