<?php
namespace MediaWiki\Extension\LinkGuesser;

use MediaWiki\MediaWikiServices;
use Status;
use SearchEngine;
use SearchEngineFactory;
use Title;

class ResultsResolver {
    public static function retrieveResults(
        Title $querySubject
    ): array {
        $services = MediaWikiServices::getInstance();

        $config = $services->getConfigFactory()->makeConfig( 'LinkGuesser' );

        $searchEngineFactory = $services->getSearchEngineFactory();
        $searchEngine = $searchEngineFactory->create();

        $strippedTitle = $querySubject->getText();

        // Below part shamelessly inspired by api/ApiQuerySearch.php
        $matches = $searchEngine->searchText( 'intitle:' . $strippedTitle );

        if ( $matches instanceof Status ) {
            $status = $matches;
            $matches = $status->getValue();
        } else {
            $status = null;
        }

        if ( $status ) {
			if ( !$status->isOK() ) {
                echo "Status not ok";
                return []; // failure
			}
		} elseif ( $matches === null ) {
            echo "Matches are null";
			return [];
		}

        $maxResultsRaw = $config->get( 'LinkGuesserResultsLimit' );
        $maxResults = is_int( $maxResultsRaw )
            ? $maxResultsRaw
            : 25; // if $wgLinkGuesserResultsLimit isn't an int, fall back to default value.

        $count = 0;
        $results = [];

        foreach ( $matches as $result ) {
			$count++;

            if ( $count > $maxResults ) {
                break;
            }

			// Silently skip broken and missing titles
			if ( $result->isBrokenTitle() || $result->isMissingRevision() ) {
				continue;
			}

			$resultTitle = $result->getTitle();
            $results[] = $resultTitle;
		}

        return $results;
    }
}