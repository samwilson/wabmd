<?php

declare( strict_types=1 );

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240226100445 extends AbstractMigration {
	public function getDescription(): string {
		return 'Create initial tables.';
	}

	public function up( Schema $schema ): void {
		$this->addSql( 'CREATE TABLE districts (
			id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			title VARCHAR(200) NOT NULL UNIQUE
		)' );

		$this->addSql( 'CREATE TABLE places (
			id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			title VARCHAR(200) NOT NULL UNIQUE
		)' );

		$this->addSql( 'CREATE TABLE births (
			id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,

			surname VARCHAR(200) NULL DEFAULT NULL,
			given_names VARCHAR(200) NULL DEFAULT NULL,
			gender VARCHAR(200) NULL DEFAULT NULL,
			father VARCHAR(200) NULL DEFAULT NULL,
			mother VARCHAR(200) NULL DEFAULT NULL,
			birth_place_id INT(10) NULL DEFAULT NULL,
			FOREIGN KEY (birth_place_id) REFERENCES places (id),
			year_of_birth VARCHAR(200) NULL DEFAULT NULL,

			registration_district_id INT(10) NULL DEFAULT NULL,
			FOREIGN KEY (registration_district_id) REFERENCES districts (id),
			registration_year VARCHAR(200) NULL DEFAULT NULL,
			registration_number VARCHAR(200) NULL DEFAULT NULL,
			UNIQUE INDEX birth_registration ( registration_year, registration_number )
		);' );

		$this->addSql( 'CREATE TABLE deaths (
			id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,

			surname VARCHAR(200) NULL DEFAULT NULL,
			given_names VARCHAR(200) NULL DEFAULT NULL,
			gender VARCHAR(200) NULL DEFAULT NULL,
			father VARCHAR(200) NULL DEFAULT NULL,
			mother VARCHAR(200) NULL DEFAULT NULL,
			death_place_id INT(10) NULL DEFAULT NULL,
			FOREIGN KEY (death_place_id) REFERENCES places (id),
			year_of_death VARCHAR(200) NULL DEFAULT NULL,
			age VARCHAR(200) NULL DEFAULT NULL,

			registration_district_id INT(10) NULL DEFAULT NULL,
			FOREIGN KEY (registration_district_id) REFERENCES districts (id),
			registration_year VARCHAR(200) NULL DEFAULT NULL,
			registration_number VARCHAR(200) NULL DEFAULT NULL,
			UNIQUE INDEX death_registration ( registration_year, registration_number )
		);' );

		$this->addSql( 'CREATE TABLE marriages (
			id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,

			surname VARCHAR(200) NULL DEFAULT NULL,
			given_names VARCHAR(200) NULL DEFAULT NULL,
			gender1 VARCHAR(200) NULL DEFAULT NULL,
			gender2 VARCHAR(200) NULL DEFAULT NULL,
			spouse_surname VARCHAR(200) NULL DEFAULT NULL,
			spouse_given_names VARCHAR(200) NULL DEFAULT NULL,
			marriage_place_id INT(10) NULL DEFAULT NULL,
			FOREIGN KEY (marriage_place_id) REFERENCES places (id),
			year_of_marriage VARCHAR(200) NULL DEFAULT NULL,

			registration_district_id INT(10) NULL DEFAULT NULL,
			FOREIGN KEY (registration_district_id) REFERENCES districts (id),
			registration_year VARCHAR(200) NULL DEFAULT NULL,
			registration_number VARCHAR(200) NULL DEFAULT NULL,
			UNIQUE INDEX marriage_registration (
				surname, given_names, spouse_surname, spouse_given_names, registration_year, registration_number
			)
		);' );
	}

	public function down( Schema $schema ): void {
		$this->addSql( 'DROP TABLE births;' );
		$this->addSql( 'DROP TABLE deaths;' );
		$this->addSql( 'DROP TABLE marriages;' );
		$this->addSql( 'DROP TABLE places;' );
		$this->addSql( 'DROP TABLE districts;' );
	}
}
