<?php

namespace App\Controller;

use App\Database;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController {

	private $db;

	public function __construct( Database $database ) {
		$this->db = $database;
	}

	#[Route( '/', name: 'home' )]
	public function index( Request $request ): Response {
		if ( $request->get( 'type' ) ) {
			return $this->redirectToRoute( 'record', [
				'type' => $request->get( 'type' ),
				'year' => $request->get( 'registration_year' ),
				'num' => $request->get( 'registration_number' ),
			] );
		}
		return $this->render( 'home.html.twig', [
			'total_count' => $this->db->getTotalRecords(),
			'year_totals' => $this->db->getYearTotals(),
		] );
	}

	#[Route( '/{type}/{year}', name: 'year',
		requirements: [ 'type' => '(births|marriages|deaths)', 'year' => '\d{4}' ]
	)]
	public function year( string $type, string $year ): Response {
		$yearData = $this->db->getYearData( $type, $year );
		if ( !$yearData ) {
			throw $this->createNotFoundException();
		}
		return $this->render( 'year.html.twig', [
			'total_count' => $this->db->getTotalRecords(),
			'type' => $type,
			'type_singular' => substr( $type, 0, -1 ),
			'year' => $year,
			'year_data' => $yearData,
		] );
	}

	#[Route( '/{type}/{year}/{num}', name: 'record',
		requirements: [ 'type' => '(birth|marriage|death)', 'year' => '\d{4}' ]
	)]
	public function record( string $type, string $year, string $num ): Response {
		$record = $this->db->getRecord( $type, $year, $num );
		if ( !$record ) {
			throw $this->createNotFoundException();
		}
		return $this->render( 'record.html.twig', [
			'total_count' => $this->db->getTotalRecords(),
			'type' => $type,
			'record' => $record,
		] );
	}
}
