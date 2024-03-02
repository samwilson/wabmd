<?php

namespace App;

use Doctrine\DBAL\Connection;

class Database {

	private Connection $conn;

	private $districts = [];

	private $places = [];

	public function __construct( Connection $conn ) {
		$this->conn = $conn;
	}

	public function getDistrictId( string $districtTitle ): int {
		if ( isset( $this->districts[$districtTitle] ) ) {
			return $this->districts[$districtTitle];
		}
		$id = $this->conn->fetchOne( 'SELECT id FROM districts WHERE title = ?', [ $districtTitle ] );
		if ( !$id ) {
			$id = $this->conn->insert( 'districts', [ 'title' => $districtTitle ] );
		}
		$this->districts[$districtTitle] = $id;
		return $id;
	}

	public function getPlaceId( string $placeTitle ): int {
		if ( isset( $this->places[$placeTitle] ) ) {
			return $this->places[$placeTitle];
		}
		$id = $this->conn->fetchOne( 'SELECT id FROM places WHERE title = ?', [ $placeTitle ] );
		if ( !$id ) {
			$id = $this->conn->insert( 'places', [ 'title' => $placeTitle ] );
		}
		$this->places[$placeTitle] = $id;
		return $id;
	}

	public function getRecord( string $type, string $year, string $num ) {
		$queryBuilder = $this->conn->createQueryBuilder()
			->select( '*' );
		switch ( $type ) {
			case 'birth':
				$queryBuilder->from( 'births' )
					->join( 'births', 'places', 'p', 'birth_place_id = p.id' )
					->addSelect( 'p.title AS birth_place' )
					->join( 'births', 'districts', 'd', 'registration_district_id = d.id' )
					->addSelect( 'd.title AS registration_district' );
				break;
			case 'death':
				$queryBuilder->from( 'deaths' );
				break;
			case 'marriage':
				$queryBuilder->from( 'marriages' );
				break;
			default:
				return [];
		}
		return $queryBuilder
			->where( 'registration_year = ?' )
			->setParameter( 0, $year )
			->andWhere( 'registration_number = ?' )
			->setParameter( 1, $num )
			->fetchAssociative();
	}

	public function getTotalRecords() {
		return $this->conn->fetchOne( 'SELECT SUM(t) FROM (
			SELECT COUNT(*) AS t FROM births
			UNION
			SELECT COUNT(*) AS t FROM deaths
			UNION
			SELECT COUNT(*) AS t FROM marriages
		) AS d;' );
	}

	public function saveMultiple( $table, $data ) {
		$columns = [];
		$values  = [];
		$set     = [];
		foreach ( $data[0] as $columnName => $value ) {
			$columns[] = $columnName;
			$set[] = '?';
		}
		foreach ( $data as $datum ) {
			foreach ( $datum as $columnName => $value ) {
				$values[] = $value;
			}
		}
		$vals = array_fill( 0, count( $data ), '(' . implode( ',', $set ) . ')' );
		$sql = 'INSERT IGNORE INTO ' . $table . ' (' . implode( ', ', $columns ) . ') VALUES ' . implode( ', ', $vals );
		$this->conn->executeStatement( $sql, $values );
	}

}
