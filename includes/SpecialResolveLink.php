<?php
/**
 * ResolveLink special page
 *
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\LinkGuesser;

class SpecialResolveLink extends SpecialPage {
	public function __construct() {
		parent::__construct( 'resolveLink' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 */
	public function execute(
        $sub
    ) {
		$request = $this->getRequest();
        $performer = $this->getUser();
        $out = $this->getOutput();
        $this->setHeaders();

        $requestedNamespaceId = $request->getText( 'ns' );
        $requestedDbKey = isset( $sub ) && $sub !== ''
            ? $sub
            : $request->getText( 'pg' );

        // Try creating a Title object with what we are passed
        // If result is null, this is invalid and we throw an error.
        $tryMakingTitle = Title::newFromText(
            $requestedDbKey,
            (int) $requestedNamespaceId
        );

        if ( $tryMakingTitle === null ) {
            $this->showError(
                'Invalid page title and/or namespace.'
            );
            return;
        }

        $results = ResultsResolver::retrieveResults( $requestedDbKey );
        
        $this->showResults(
            $results
        );
	}

	protected function getGroupName() {
		return 'other';
	}

    private function showError(
        string $message
    ) {
        $output = $this->getOutput();
        $output->wrapWikiMsg(
            "<div class=\"errorbox\"><p>$message</p></div>"
        );
    }

    private function showResults(
        array $results
    ) {
        $out = $this->getOutput();

        $out->enableOOUI();

        // TODO: Display the results
        // TODO: Create links for each result to click on
        // TODO: If performer has edit rights, allow them to correct the previous link
    }
}