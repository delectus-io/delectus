<?php

/**
 * DelectusResourceOwnerExtension adds relationships 'Files' and 'Links' to an extended model
 *
 * @method \ManyManyList Files()
 * @method \ManyManyList Links()
 */
class DelectusResourceOwnerExtension extends DataExtension {
	private static $many_many = [
		'Files' => 'File',
		'Links' => 'DelectusLinkModel',
	];

	private static $many_many_extraFields = [
		'Files' => [
			'RelatedDate' => 'SS_Datetime',
			'RelatedByID' => 'Int',
		],
		'Links' => [
			'RelatedDate' => 'SS_Datetime',
			'RelatedByID' => 'Int',
		],
	];

	public function getResources() {
		$resources = new ArrayList();
		$resources->merge( $this->owner->Files() );
		$resources->merge( $this->owner->Links() );
		$resources->sort('LastEdited desc');
		return $resources;
	}

	public function canViewFile( $idOrPathOrFile ) {
		if ( is_int( $idOrPathOrFile ) ) {
			$file = File::get()->byID( $idOrPathOrFile );
		} elseif ( ! is_object( $idOrPathOrFile ) ) {
			$file = File::get()->filter( [
				'Filename' => $idOrPathOrFile,
			] )->first();
		} else {
			$file = $idOrPathOrFile;
		}

		return $file && ( $this->owner->Files()->filter( [
					'FileID' => $file->ID,
				] )->count() > 0 );
	}

	public function canViewLink( $idOrURLOrDelectusLink ) {
		if ( is_int( $idOrURLOrDelectusLink ) ) {
			$link = DelectusLinkModel::get()->byID( $idOrURLOrDelectusLink );
		} elseif ( ! is_object( $idOrURLOrDelectusLink ) ) {
			$link = DelectusLinkModel::get()->filter( [
				'URL' => $idOrURLOrDelectusLink,
			] )->first();
		} else {
			$link = $idOrURLOrDelectusLink;
		}

		return $link && ( $this->owner->Links()->filter( [
					'LinkID' => $link->ID,
				] )->count() > 0 );
	}
}