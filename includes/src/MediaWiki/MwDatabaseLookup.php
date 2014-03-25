<?php

namespace SMW\MediaWiki;

use Title;

/**
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class MwDatabaseLookup {

	/** @var Database */
	protected $database = null;

	protected $namespace = array();

	/**
	 * @since 1.9.2
	 *
	 * @param Database $database
	 */
	public function __construct( Database $database ) {
		$this->database = $database;
	}

	/**
	 * @since 1.9.2
	 *
	 * @param int $namespace
	 *
	 * @return MwDatabaseLookup
	 */
	public function byNamespace( $namespace ) {
		$this->namespace = array( 'page_namespace' => $namespace );
		return $this;
	}

	/**
	 * @since 1.9.2
	 *
	 * @return null|Title[]
	 */
	public function findAllTitles() {

		$res = $this->database->select(
			'page',
			array( 'page_namespace', 'page_title' ),
			$this->namespace,
			__METHOD__,
			array( 'USE INDEX' => 'PRIMARY' )
		);

		return $this->makeTitles( $res );
	}

	/**
	 * @since 1.9.2
	 *
	 * @param int $startId
	 * @param int $endId
	 *
	 * @return null|Title[]
	 */
	public function findTitlesByRange( $startId = 0, $endId = 0 ) {

		$res = $this->database->select(
			'page',
			array( 'page_namespace', 'page_title', 'page_id' ),
			array( "page_id BETWEEN $startId AND $endId" ) + $this->namespace,
			__METHOD__,
			array( 'ORDER BY' => 'page_id ASC', 'USE INDEX' => 'PRIMARY' )
		);

		return $this->makeTitles( $res );
	}

	/**
	 * @since 1.9.2
	 *
	 * @return int
	 */
	public function getMaxPageId() {
		return $this->database->selectField(
			'page',
			'MAX(page_id)',
			false,
			__METHOD__
		);
	}

	protected function makeTitles( $res ) {

		$pages = array();

		if ( $res === false ) {
			return $pages;
		}

		foreach ( $res as $row ) {
			$pages[] = Title::makeTitle( $row->page_namespace, $row->page_title );
		}

		return $pages;
	}

}
