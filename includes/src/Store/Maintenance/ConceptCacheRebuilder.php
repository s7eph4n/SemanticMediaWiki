<?php

namespace SMW\Store\Maintenance;

use SMW\MediaWiki\MwDatabaseLookup;
use SMW\MessageReporter;
use SMW\Settings;
use SMW\Store;
use SMW\DIConcept;

use Title;

/**
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class ConceptCacheRebuilder {

	/** @var MessageReporter */
	protected $reporter;

	/** @var Store */
	protected $store;

	/** @var Settings */
	protected $settings;

	private $concept = null;
	private $action  = null;
	private $outputLevel = 1;
	private $options = array();
	private $startId = 0;
	private $endId   = 0;
	private $lines   = 0;

	/**
	 * @since 1.9.2
	 *
	 * @param Store $store
	 * @param Settings $settings
	 * @param MessageReporter $reporter
	 */
	public function __construct( Store $store, Settings $settings, MessageReporter $reporter ) {
		$this->store = $store;
		$this->settings = $settings;
		$this->reporter = $reporter;
	}

	/**
	 * @since 1.9.2
	 *
	 * @param array $parameters
	 */
	public function setParameters( array $parameters ) {

		$options = array( 'hard', 'update', 'old', 'quiet', 'status', 'verbose' );

		foreach ( $options as $option ) {
			if ( isset( $parameters[$option] ) ) {
				$this->options[$option] = $parameters[$option];
			}
		}

		if ( isset( $parameters['concept'] ) ) {
			$this->concept = $parameters['concept'];
		}

		if ( isset( $parameters['s'] ) ) {
			$this->startId = intval( $parameters['s'] );
		}

		if ( isset( $parameters['e'] ) ) {
			$this->endId = intval( $parameters['e'] );
		}

		$actions = array( 'status', 'create', 'delete' );

		foreach ( $actions as $action ) {
			if ( isset( $parameters[$action] ) && $this->action === null ) {
				$this->action = $action;
			}
		}

		$this->outputLevel = $this->hasOption( 'quiet' ) ? 0 : $this->hasOption( 'verbose' ) ? 2 : 1;
	}

	/**
	 * @since 1.9.2
	 *
	 * @return boolean
	 */
	public function rebuild() {

		switch ( $this->action ) {
			case 'status':
				$this->reporter->reportMessage( "\nDisplaying concept cache status information. Use CTRL-C to abort.\n\n" );
				break;
			case 'create':
				$this->reporter->reportMessage(  "\nCreating/updating concept caches. Use CTRL-C to abort.\n\n" );
				break;
			case 'delete':
				$delay = 5;
				$this->reporter->reportMessage( "\nAbort with CTRL-C in the next $delay seconds ... " );
				!$this->hasOption( 'quiet' ) ? wfCountDown( $delay ) : '';
				$this->reporter->reportMessage( "\nDeleting concept caches.\n\n" );
				break;
			default:
				return false;
		}

		if ( $this->hasOption( 'hard' ) ) {

			$settings  = ' smwgQMaxDepth: ' . $this->settings->get( 'smwgQMaxDepth' );
			$settings .= ' smwgQMaxSize: '  . $this->settings->get( 'smwgQMaxSize' );
			$settings .= ' smwgQFeatures: ' . $this->settings->get( 'smwgQFeatures' );

			$this->reporter->reportMessage( "Option 'hard' is parameterized by{$settings}\n\n", 2 );
		}

		$concepts = $this->getConcepts();

		foreach ( $concepts as $concept ) {
			$this->workOnConcept( $concept );
		}

		if ( $concepts === array() ) {
			$this->reporter->reportMessage( "No concept available.\n", 2 );
		}

		return true;
	}

	protected function workOnConcept( Title $title ) {

		$concept = $this->store->getConceptCacheStatus( $title );

		if ( $this->validateConceptCacheStatus( $title, $concept ) ) {
			return $this->lines += $this->outputLevel < 2 ? 0 : 1;
		}

		$result = $this->performActionAndReport( $title, $concept );

		if ( $result ) {
			$this->reporter->reportMessage( '  ' . implode( $result, "\n  " ) . "\n" );
		}

		return $this->lines += 1;
	}

	protected function validateConceptCacheStatus( $title, $concept = null ) {

		$skip = false;

		if ( $concept === null ) {
			$skip = 'page not cachable (no concept description, maybe a redirect)';
		} elseif ( ( $this->hasOption( 'update' ) ) && ( $concept->getCacheStatus() !== 'full' ) ) {
			$skip = 'page not cached yet';
		} elseif ( ( $this->hasOption( 'old' ) ) && ( $concept->getCacheStatus() === 'full' ) &&
			( $concept->getCacheDate() > ( strtotime( 'now' ) - intval( $this->options['old'] ) * 60 ) ) ) {
			$skip = 'cache is not old yet';
		} elseif ( ( $this->hasOption( 'hard' ) ) && ( $this->settings->get( 'smwgQMaxSize' ) >= $concept->getSize() ) &&
					( $this->settings->get( 'smwgQMaxDepth' ) >= $concept->getDepth() &&
					( ( ~( ~( $concept->getQueryFeatures() + 0 ) | $this->settings->get( 'smwgQFeatures' ) ) ) == 0 ) ) ) {
			$skip = 'concept is not "hard" according to wiki settings';
		}

		if ( $skip ) {
			$this->reporter->reportMessage(
				$this->lines !== false ? "($this->lines) " : '' .
				'Skipping concept "' .
				$title->getPrefixedText() .
				"\": $skip\n",
				2
			);
		}

		return $skip;
	}

	protected function performActionAndReport( Title $title, DIConcept $concept ) {

		$this->reporter->reportMessage( "($this->lines) " );

		if ( $this->action ===  'create' ) {
			$this->reporter->reportMessage( 'Creating cache for "' . $title->getPrefixedText() . "\" ...\n" );
			return $this->store->refreshConceptCache( $title );
		}

		if ( $this->action === 'delete' ) {
			$this->reporter->reportMessage( 'Deleting cache for "' . $title->getPrefixedText() . "\" ...\n" );
			return $this->store->deleteConceptCache( $title );
		}

		$this->reporter->reportMessage( 'Status of cache for "' . $title->getPrefixedText() . '": ' );

		if ( $concept->getCacheStatus() === 'full' ) {
			return $this->reporter->reportMessage(
				'Cache created at ' .
				date( 'Y-m-d H:i:s', $concept->getCacheDate() ) .
				' (' . floor( ( strtotime( 'now' ) - $concept->getCacheDate() ) / 60 ) . ' minutes old), ' .
				"{$concept->getCacheCount()} elements in cache\n"
			);
		}

		$this->reporter->reportMessage( "Not cached.\n" );
	}

	protected function getConcepts() {

		if ( $this->concept !== null ) {
			return array( $this->acquireSingleConcept() );
		}

		return $this->acquireMultipleConcepts();
	}

	protected function acquireSingleConcept() {
		return Title::newFromText( $this->concept, SMW_NS_CONCEPT );
	}

	protected function acquireMultipleConcepts() {

		$databaseLookup = new MwDatabaseLookup( $this->store->getDatabase() );
		$databaseLookup->byNamespace( SMW_NS_CONCEPT );

		if ( $this->endId == 0 && $this->startId == 0 ) {
			return $databaseLookup->findAllTitles();
		}

		$endId = $databaseLookup->getMaxPageId();

		if ( $this->endId > 0 ) {
			$endId = min( $this->endId, $endId );
		}

		return $databaseLookup->findTitlesByRange( $this->startId, $endId );
	}

	protected function hasOption( $key ) {
		return isset( $this->options[$key] );
	}

}
