<?php

declare( strict_types=1 );

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240305000000 extends AbstractMigration {

	public function getDescription(): string {
		return 'Add wikidata and wikitree.';
	}

	public function up( Schema $schema ): void {
		$this->addSql( 'ALTER TABLE births
			ADD wikidata VARCHAR(50) NULL,
			ADD INDEX births_wikidata (wikidata),
			ADD wikitree VARCHAR(50) NULL,
			ADD INDEX births_wikitree (wikitree)
		' );
		$this->addSql( 'ALTER TABLE marriages
			ADD wikidata VARCHAR(50) NULL,
			ADD INDEX marriages_wikidata (wikidata),
			ADD wikitree VARCHAR(50) NULL,
			ADD INDEX marriages_wikitree (wikitree)
		' );
			$this->addSql( 'ALTER TABLE deaths
			ADD wikidata VARCHAR(50) NULL,
			ADD INDEX deaths_wikidata (wikidata),
			ADD wikitree VARCHAR(50) NULL,
			ADD INDEX deaths_wikitree (wikitree)
		' );
	}
}
