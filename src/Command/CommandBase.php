<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'app:download',
	description: 'Download records from the WA BDM index into JSON and CSV files in the data/ directory.',
)]
class CommandBase extends Command {

	private string $dataDir;

	protected array $types = [
		'births' => [
			'plural' => 'births',
			'singular' => 'birth',
			'fields' => [
				// Map the source field name to DB field name.
				'surname' => 'surname',
				'givenNames' => 'given_names',
				'gender' => 'gender',
				'father' => 'father',
				'mother' => 'mother',
				'birthPlace' => 'birth_place_id',
				'yearOfBirth' => 'year_of_birth',
				'registrationDistrict' => 'registration_district_id',
				'registrationNumber' => 'registration_number',
				'registrationYear' => 'registration_year',
			],
		],
		'deaths' => [
			'plural' => 'deaths',
			'singular' => 'death',
			'fields' => [
				// Map the source field name to DB field name.
				'surname' => 'surname',
				'givenNames' => 'given_names',
				'gender' => 'gender',
				'father' => 'father',
				'mother' => 'mother',
				'deathPlace' => 'death_place_id',
				'yearOfDeath' => 'year_of_death',
				'age' => 'age',
				'registrationDistrict' => 'registration_district_id',
				'registrationNumber' => 'registration_number',
				'registrationYear' => 'registration_year',
			],
		],
		'marriages' => [
			'plural' => 'marriages',
			'singular' => 'marriage',
			'fields' => [
				// Map the source field name to DB field name.
				'surname' => 'surname',
				'givenNames' => 'given_names',
				'gender01' => 'gender1',
				'gender02' => 'gender2',
				'spouseSurname' => 'spouse_surname',
				'spouseGivenNames' => 'spouse_given_names',
				'marriagePlace' => 'marriage_place_id',
				'yearOfMarriage' => 'year_of_marriage',
				'registrationDistrict' => 'registration_district_id',
				'registrationNumber' => 'registration_number',
				'registrationYear' => 'registration_year',
			],
		],
	];

	protected function getDataDir(): string {
		return $this->dataDir;
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int {
		$this->dataDir = dirname( __DIR__, 2 ) . '/public/data';
		if ( !is_dir( $this->dataDir ) ) {
			mkdir( $this->dataDir );
		}
		return Command::SUCCESS;
	}
}
