<?php

namespace App\Command;

use App\Database;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'app:import',
	description: 'Import the downloaded data into the local database, from the data/ directory.',
)]
class ImportCommand extends CommandBase {

	private $db;

	public function __construct( Database $db ) {
		parent::__construct();
		$this->db = $db;
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int {
		parent::execute( $input, $output );
		$io = new SymfonyStyle( $input, $output );
		$progressBar = $io->createProgressBar();
		foreach ( $this->types as $info ) {
			$io->writeln( 'Importing ' . $info['plural'] );
			$progressBar->start();
			$file = fopen( $this->getDataDir() . '/' . $info['plural'] . '.csv', 'r' );
			$rowNum = 0;
			$dataChunk = [];
			$row = true;
			while ( $row !== false ) {
				$row = fgetcsv( $file );
				$rowNum++;
				if ( $rowNum === 1 || !$row ) {
					continue;
				}
				$rowData = array_combine( $info['fields'], $row );
				$rowData['registration_district_id'] = $this->db->getDistrictId(
					$rowData['registration_district_id']
				);
				$placeColName = $info['singular'] . '_place_id';
				$rowData[$placeColName] = $this->db->getPlaceId( $rowData[$placeColName] );
				$dataChunk[] = $rowData;
				// Save every 100 records.
				if ( count( $dataChunk ) === 100 ) {
					$this->db->saveMultiple( $info['plural'], $dataChunk );
					$dataChunk = [];
					$progressBar->advance( 100 );
				}
			}
			$io->newLine();
		}
		return Command::SUCCESS;
	}

	private function saveChunck( $data ) {
		$rows = [];
		foreach ( $data as $datum ) {
			$districtId = $this->db->getDistrictId( $datum['registrationDistrict'] );
			$birthPlaceId = $this->db->getPlaceId( $datum['birthPlace'] );
			$birthRecords[] = [
				'surname' => $datum['surname'],
				'given_names' => $datum['givenNames'],
				'gender' => $datum['gender'],
				'father' => $datum['father'],
				'mother' => $datum['mother'],
				'birth_place_id' => $birthPlaceId,
				'year_of_birth' => $datum['yearOfBirth'],
				'year_of_birth_from' => $datum['yearOfBirthFrom'],
				'year_of_birth_to' => $datum['yearOfBirthTo'],

				'registration_district_id' => $districtId,
				'registration_year' => $datum['registrationYear'],
				'registration_number' => $datum['registrationNumber'],

				'record_id' => $datum['recordID'],
				'record_type_id' => $datum['recordTypeID'],
			];
		}
		$this->db->saveMultiple( $rows );
	}
}
