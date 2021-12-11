<?php
namespace MediaWiki\Extension\LinkGuesser;
use SearchEngine;
use SearchEngineFactory;

class ResultsResolver {
    public static function retrieveResults(
        string $dbKey
    ): array {
        $services = MediaWikiServices::getInstance();
        $searchEngineFactory = $services->getSearchEngineFactory();
        $searchEngine = $searchEngineFactory->create();

        // Below part shamelessly inspired by api/ApiQuerySearch.php
        $matches = $searchEngine->searchTitle( $dbKey );

        if ( $matches instanceof Status ) {
            $status = $matches;
            $matches = $status->getValue();
        }

        if ( $status ) {
			if ( !$status->isOK() ) {
                return []; // failure
			}
		} elseif ( $matches === null ) {
			return [];
		}

        $count = 0;
        $results = [];

        foreach ( $matches as $result ) {
			$count++;
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