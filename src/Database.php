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
			$this->conn->insert( 'districts', [ 'title' => $districtTitle ] );
			$id = $this->conn->lastInsertId();
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
			$this->conn->insert( 'places', [ 'title' => $placeTitle ] );
			$id = $this->conn->lastInsertId();
		}
		$this->places[$placeTitle] = $id;
		return $id;
	}

	private function getTypeQueryBuilder( $type ) {
		$queryBuilder = $this->conn->createQueryBuilder();
		switch ( $type ) {
			case 'birth':
			case 'births':
					$queryBuilder->from( 'births' )
					->select( 'births.*' )
					->join( 'births', 'places', 'p', 'birth_place_id = p.id' )
					->addSelect( 'p.title AS birth_place' )
					->join( 'births', 'districts', 'd', 'registration_district_id = d.id' )
					->addSelect( 'd.title AS registration_district' );
				break;
			case 'marriage':
			case 'marriages':
					$queryBuilder->from( 'marriages' )
					->select( 'marriages.*' )
					->join( 'marriages', 'places', 'p', 'marriage_place_id = p.id' )
					->addSelect( 'p.title AS marriage_place' )
					->join( 'marriages', 'districts', 'd', 'registration_district_id = d.id' )
					->addSelect( 'd.title AS registration_district' );
				break;
			case 'death':
			case 'deaths':
					$queryBuilder->from( 'deaths' )
					->select( 'deaths.*' )
					->join( 'deaths', 'places', 'p', 'death_place_id = p.id' )
					->addSelect( 'p.title AS death_place' )
					->join( 'deaths', 'districts', 'd', 'registration_district_id = d.id' )
					->addSelect( 'd.title AS registration_district' );
				break;
		}
		return $queryBuilder;
	}

	public function getRecord( string $type, string $year, string $num ) {
		return $this->getTypeQueryBuilder( $type )
			->where( 'registration_year = ?' )
			->setParameter( 0, $year )
			->andWhere( 'registration_number = ?' )
			->setParameter( 1, $num )
			->fetchAssociative();
	}

	public function getYearData( $type, $year ) {
		$qb = $this->getTypeQueryBuilder( $type );
		if ( $type === 'births' ) {
			$qb->where( 'year_of_birth = ?' );
		} elseif ( $type === 'marriages' ) {
			$qb->where( 'year_of_marriage = ?' );
		} elseif ( $type === 'deaths' ) {
			$qb->where( 'year_of_death = ?' );
		}
		return $qb->setParameter( 0, $year )
			->orderBy( 'CAST( registration_number AS UNSIGNED )' )
			->fetchAllAssociative();
	}

	public function getPlaceData( string $type, int $placeId ) {
		$qb = $this->getTypeQueryBuilder( $type );
		if ( $type === 'births' ) {
			$qb->where( 'birth_place_id = ?' );
		} elseif ( $type === 'marriages' ) {
			$qb->where( 'marriage_place_id = ?' );
		} elseif ( $type === 'deaths' ) {
			$qb->where( 'death_place_id = ?' );
		}
		return $qb->setParameter( 0, $placeId )
			->fetchAllAssociative();
	}

	public function getPlaceTitle( int $id ): string {
		return $this->conn->fetchOne( 'SELECT title FROM places WHERE id = ?', [ $id ] );
	}

	public function getYearTotals() {
		$births = $this->conn->executeQuery( '
			SELECT year_of_birth AS year, COUNT(id) AS total, COUNT(wikitree) AS wikitree
				FROM births
				GROUP BY year_of_birth
				ORDER BY year_of_birth
		' )->fetchAllAssociativeIndexed();
		$marriages = $this->conn->executeQuery( '
			SELECT year_of_marriage AS year, COUNT(id) AS total
				FROM marriages
				GROUP BY year_of_marriage
				ORDER BY year_of_marriage
		' )->fetchAllAssociativeIndexed();
		$deaths = $this->conn->executeQuery( '
			SELECT year_of_death AS year, COUNT(id) AS total, COUNT(wikitree) AS wikitree
				FROM deaths
				GROUP BY year_of_death
				ORDER BY year_of_death
		' )->fetchAllAssociativeIndexed();
		$years = array_filter( array_merge(
			array_keys( $births ),
			array_keys( $marriages ),
			array_keys( $deaths )
		), 'is_numeric' );
		$out = [];
		for ( $y = min( $years ); $y <= max( $years ); $y++ ) {
			$out[ $y ] = [
				'births' => [
					'total' => $births[$y]['total'] ?? 0,
					'wikitree' => $births[$y]['wikitree'] ?? 0,
				],
				'marriages' => [
					'total' => $marriages[$y]['total'] ?? 0,
					'wikitree' => 0,
				],
				'deaths' => [
					'total' => $deaths[$y]['total'] ?? 0,
					'wikitree' => $deaths[$y]['wikitree'] ?? 0,
				],
			];
		}
		return $out;
	}

	public function getTotalWikidata() {
		return $this->conn->fetchOne( 'SELECT SUM(t) FROM (
			SELECT COUNT(*) AS t FROM births WHERE wikidata IS NOT NULL
			UNION
			SELECT COUNT(*) AS t FROM deaths WHERE wikidata IS NOT NULL
			UNION
			SELECT COUNT(*) AS t FROM marriages WHERE wikidata IS NOT NULL
		) AS d;' );
	}

	public function getTotalWikiTree() {
		return $this->conn->fetchOne( 'SELECT SUM(t) FROM (
			SELECT COUNT(*) AS t FROM births WHERE wikitree IS NOT NULL
			UNION
			SELECT COUNT(*) AS t FROM deaths WHERE wikitree IS NOT NULL
			UNION
			SELECT COUNT(*) AS t FROM marriages WHERE wikitree IS NOT NULL
		) AS d;' );
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

	public function saveRecordWikidata( string $table, int $id, string $wikidata ) {
		$this->conn->executeStatement(
			"UPDATE $table SET wikidata=? WHERE id=?",
			[ $wikidata, $id ]
		);
	}

	public function saveRecordWikiTree( string $table, int $id, string $wikitree ) {
		$this->conn->executeStatement(
			"UPDATE $table SET wikitree=? WHERE id=?",
			[ $wikitree, $id ]
		);
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

	public function searchPlaces( string $type, string $searchTerm ): array {
		$table = 'births';
		$joinCol = 'birth_place_id';
		if ( $type === 'deaths' ) {
			$table = 'deaths';
			$joinCol = 'death_place_id';
		}
		$sql = "SELECT p.id, p.title, COUNT(j.id) AS count
		FROM places p
			JOIN $table j ON ( p.id = j.$joinCol )
		WHERE p.title LIKE ?
		GROUP BY p.id";
		return $this->conn->fetchAllAssociative( $sql, [ "%$searchTerm%" ] );
	}

	public function searchPeople( string $searchTerm ) {
		$sql = "(
			SELECT
				'birth' AS type,
				surname,
				given_names,
				gender,
				father,
				mother,
				p.title AS place_title,
				year_of_birth AS year,
				registration_district_id,
				registration_year,
				registration_number,
				wikidata,
				wikitree
			FROM births JOIN places p ON ( p.id=birth_place_id ) WHERE
				surname LIKE ?
				OR given_names LIKE ?
				OR father LIKE ?
				OR mother LIKE ?
			) UNION (
			SELECT
				'death' AS type,
				surname,
				given_names,
				gender,
				father,
				mother,
				p.title AS place_title,
				year_of_death AS year,
				registration_district_id,
				registration_year,
				registration_number,
				wikidata,
				wikitree
			FROM deaths JOIN places p ON ( p.id=death_place_id ) WHERE
				surname LIKE ?
				OR given_names LIKE ?
				OR father LIKE ?
				OR mother LIKE ?
			) ORDER BY year ASC";
		return $this->conn->fetchAllAssociative( $sql, array_fill( 0, 8, "%$searchTerm%" ) );
	}
}
