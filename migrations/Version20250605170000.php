<?php

declare( strict_types=1 );

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250605170000 extends AbstractMigration {

	public function getDescription(): string {
		return 'Add indices for counting WikiTree progress by year.';
	}

	public function up( Schema $schema ): void {
		$this->addSql( 'ALTER TABLE births ADD INDEX idx_yearofbirth_wikitree (year_of_birth, wikitree)');
		$this->addSql( 'ALTER TABLE deaths ADD INDEX idx_yearofdeath_wikitree (year_of_death, wikitree)');
	}
}
