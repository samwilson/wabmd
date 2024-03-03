<?php

declare( strict_types=1 );

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240304134634 extends AbstractMigration {

	public function getDescription(): string {
		return 'Add indexes to years.';
	}

	public function up( Schema $schema ): void {
		$this->addSql( 'ALTER TABLE births ADD INDEX(year_of_birth);' );
		$this->addSql( 'ALTER TABLE marriages ADD INDEX(year_of_marriage);' );
		$this->addSql( 'ALTER TABLE deaths ADD INDEX(year_of_death);' );
	}
}
