<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporterFactory;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\ApplicationFactory;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class RebuildFulltextSearchTable extends \Maintenance {

	public function __construct() {
		$this->mDescription = 'Rebuild the fulltext search index (only works with SQLStore)';
		$this->addOption( 'report-runtime', 'Report execution time and memory usage', false );
		$this->addOption( 'with-maintenance-log', 'Add log entry to `Special:Log` about the maintenance run.', false );
		$this->addOption( 'v', 'Show additional (verbose) information about the progress', false );
		$this->addOption( 'quick', 'Suppress abort operation', false );

		parent::__construct();
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( !defined( 'SMW_VERSION' ) || !$GLOBALS['smwgSemanticsEnabled'] ) {
			$this->output( "You need to have SMW enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$fulltextSearchTableFactory = new FulltextSearchTableFactory();

		// Only the SQLStore is supported
		$searchTableRebuilder = $fulltextSearchTableFactory->newSearchTableRebuilder(
			$applicationFactory->getStore( '\SMW\SQLStore\SQLStore' )
		);

		$searchTableRebuilder->reportVerbose(
			$this->hasOption( 'v' )
		);

		$this->reportMessage(
			"\nThe script rebuilds the search index from property tables that\n" .
			"support a fulltext search. Any change of the index rules (altered\n".
			"stopwords, new stemmer etc.) and/or a newly added or altered table\n".
			"requires to run this script again to ensure that the index complies\n".
			"with the rules set forth by the DB or Sanitizer.\n\n"
		);

		$searchTable = $searchTableRebuilder->getSearchTable();
		$textSanitizer = $fulltextSearchTableFactory->newTextSanitizer();

		foreach ( $textSanitizer->getVersions() as $key => $value ) {
			$this->reportMessage( "\r". sprintf( "%-35s%s", "- {$key}", $value )  . "\n" );
		}

		$this->reportMessage(
			"\nThe following properties are exempted from the fulltext search index.\n"
		);

		$exemptionList = '';

		foreach ( $searchTable->getPropertyExemptionList() as $prop ) {
			$exemptionList .= ( $exemptionList === '' ? '' : ', ' ) . $prop;

			if ( strlen( $exemptionList ) > 60 ) {
				$this->reportMessage( "\n- " . $exemptionList );
				$exemptionList = '';
			}
		}

		$this->reportMessage( "\n- " . $exemptionList . "\n\n" );

		$this->reportMessage(
			"The entire index table is going to be purged first and \n" .
			"it may take a moment before the rebuild is completed due to\n" .
			"dependencies on table content and selected options.\n"
		);

		if ( !$this->hasOption( 'quick' ) ) {
			$this->reportMessage( "\n" . 'Abort the rebuild with control-c in the next five seconds ...  ' );
			wfCountDown( 5 );
		}

		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		// Need to instantiate an extra object here since we cannot make this class itself
		// into a MessageReporter since the maintenance script does not load the interface in time.
		$reporter = MessageReporterFactory::getInstance()->newObservableMessageReporter();
		$reporter->registerReporterCallback( array( $this, 'reportMessage' ) );

		$searchTableRebuilder->setMessageReporter( $reporter );
		$result = $searchTableRebuilder->run();

		if ( $result && $this->hasOption( 'report-runtime' ) ) {
			$this->reportMessage(
				"\n" . $maintenanceHelper->getFormattedRuntimeValues() . "\n"
			);
		}

		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$maintenanceLogger = $maintenanceFactory->newMaintenanceLogger( 'RebuildFulltextSearchTableLogger' );
			$maintenanceLogger->log( $maintenanceHelper->getFormattedRuntimeValues() );
		}

		$maintenanceHelper->reset();
		return $result;
	}

	/**
	 * @see Maintenance::reportMessage
	 *
	 * @since 2.5
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

}

$maintClass = 'SMW\Maintenance\RebuildFulltextSearchTable';
require_once ( RUN_MAINTENANCE_IF_MAIN );
