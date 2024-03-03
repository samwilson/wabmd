<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
	name: 'app:download',
	description: 'Download records from the WA BMD index into JSON and CSV files in the data/ directory.',
)]
class DownloadCommand extends CommandBase {

	private HttpClientInterface $httpClient;

	private SymfonyStyle $io;

	public function __construct( HttpClientInterface $httpClient ) {
		parent::__construct();
		$this->httpClient = $httpClient;
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int {
		parent::execute( $input, $output );
		$this->io = new SymfonyStyle( $input, $output );

		// $types = [
		// 	'births' => [
		// 		'plural' => 'births',
		// 		'singular' => 'birth',
		// 		'fields' => [
		// 			'surname',
		// 			'givenNames',
		// 			'gender',
		// 			'father',
		// 			'mother',
		// 			'birthPlace',
		// 			'registrationDistrict',
		// 			'registrationNumber',
		// 			'registrationYear',
		// 		],
		// 	],
		// 	'deaths' => [
		// 		'plural' => 'deaths',
		// 		'singular' => 'death',
		// 		'fields' => [
		// 			'surname',
		// 			'givenNames',
		// 			'gender',
		// 			'father',
		// 			'mother',
		// 			'deathPlace',
		// 			'yearOfDeath',
		// 			'age',
		// 			'registrationDistrict',
		// 			'registrationNumber',
		// 			'registrationYear',
		// 		],
		// 	],
		// 	'marriages' => [
		// 		'plural' => 'marriages',
		// 		'singular' => 'marriage',
		// 		'fields' => [
		// 			'surname',
		// 			'givenNames',
		// 			'gender01',
		// 			'gender02',
		// 			'spouseSurname',
		// 			'spouseGivenNames',
		// 			'marriagePlace',
		// 			'registrationYear',
		// 			'registrationNumber',
		// 			'registrationDistrict',
		// 			'yearOfMarriage',
		// 		],
		// 	],
		// ];
		foreach ( $this->types as $info ) {
			$this->getTypeData( $info['plural'], $info['singular'], array_keys( $info['fields'] ) );
		}
		return Command::SUCCESS;
	}

	private function getTypeData( $typePlural, $typeSingular, $fieldNames ) {
		$dataDir = $this->getDataDir();
		$jsonFilename = $dataDir . "/$typePlural.json";
		$csvFilename = $dataDir . "/$typePlural.csv";

		if ( !file_exists( $jsonFilename ) ) {
			$this->io->writeln( "Downloading $typePlural JSON data" );
			$url = "https://justice.wa.gov.au/_apps/DoJWebsite/onlineIndex/" . $typeSingular . "Records";
			$response = $this->httpClient->request( 'GET', $url );
			file_put_contents( $jsonFilename, $response->getContent() );
		}

		// Get the JSON data.
		$json = file_get_contents( $jsonFilename );
		$data = json_decode( $json, true );
		$this->io->writeln(
			'Writing ' . number_format( count( $data ) ) . ' ' . $typeSingular . ' rows to ' . $csvFilename
		);

		$dataNew = [];
		foreach ( $data as $datum ) {
			// Get only the required fields, in the order we want them.
			$datumFiltered = [];
			foreach ( $fieldNames as $field ) {
				$datumFiltered[] = $datum[$field];
			}
			// Hacky way to get a single CSV line.
			$f = fopen( 'php://memory', 'r+' );
			fputcsv( $f, $datumFiltered );
			rewind( $f );
			$csvLine = stream_get_contents( $f );
			$dataNew[] = $csvLine;
		}

		// Sort and output.
		sort( $dataNew );
		file_put_contents( $csvFilename, implode( ',', $fieldNames ) . "\n" . implode( '', $dataNew ) );
	}

}
